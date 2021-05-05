<?php
namespace Pitch\AdrBundle\Command;

use Throwable;
use Pitch\AdrBundle\Util\ClassFinder;
use Pitch\AdrBundle\Responder\Responder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

final class ResponderDebugCommand extends Command
{
    const TYPES = [
        'bool',
        'int',
        'string',
        'array',
        'float',
        'resource',
        'object',
        'resource',
        'null',
    ];

    protected static $defaultName = 'debug:responder';

    protected ClassFinder $classFinder;

    protected Responder $responder;

    public function __construct(
        Responder $responder,
        ClassFinder $classFinder
    ) {
        parent::__construct();

        $this->classFinder = $classFinder;
        $this->responder = $responder;
    }

    public function configure()
    {
        $this->setDefinition([
            new InputArgument('type', InputArgument::OPTIONAL, 'Payload type to display the handlers for'),
        ]);
    }

    public function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $handlerMap = $this->responder->getHandlerMap();
        
        $type = $input->getArgument('type');
        if ($type === null) {
            $types = \array_keys($handlerMap);
        } elseif (\in_array(Responder::TYPETRANSLATE[$type] ?? $type, $this::TYPES)) {
            $types = [Responder::TYPETRANSLATE[$type] ?? $type];
        } else {
            $classes = $this->classFinder->findClasses($type);

            if (\count($classes) === 0) {
                $output->writeln([
                    'No matching class or interface found.',
                ]);
                return 1;
            } elseif (\count($classes) === 1) {
                $className = \reset($classes);
            } elseif (\is_string($bestMatch = $this->classFinder->findBestMatch($type, $classes))) {
                $output->writeln([
                    \sprintf('Best match out of %d.', \count($classes)),
                ]);
                $className = $bestMatch;
            } else {
                $h = new QuestionHelper();
                $className = $h->ask($input, $output, new ChoiceQuestion(
                    'Multiple matching classes or interfaces found. Please select:',
                    $classes
                ));
            }

            try {
                $parents = \class_parents($className);
                $interfaces = \class_implements($className);
            } catch (Throwable $e) {
                $output->writeln([
                    \sprintf('Could not load parents and interfaces for %s.', $className),
                    \preg_replace('/^/m', '    ', $e->getMessage()),
                    'List of handlers might be incomplete.',
                ]);
                $parents = [];
                $interfaces = [];
            }
    
            $types = [
                $className,
                ...\array_values($parents),
                ...\array_values($interfaces),
                'object',
            ];
        }

        $output->writeln([
            '',
            $t = 'Response handlers' . ($type !== null ? ' for ' . $types[0] : ''),
            \str_pad('', \strlen($t), '-'),
        ]);

        foreach ($types as $type) {
            if (!isset($handlerMap[$type])) {
                continue;
            }

            $priorityLen = \max(\array_map(fn($p) => \strlen((string)$p), \array_column($handlerMap[$type], 1)));
            $output->writeln([
                $type,
            ]);
            foreach ($handlerMap[$type] as $h) {
                $output->writeln([
                    "    " . \str_pad($h[1], $priorityLen, ' ', \STR_PAD_LEFT) . "  " . $h[0],
                ]);
            }
        }
       
        $output->writeln('');

        return 0;
    }
}
