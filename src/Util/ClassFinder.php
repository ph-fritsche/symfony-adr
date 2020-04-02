<?php
namespace nextdev\AdrBundle\Util;

use Composer\Autoload\ClassLoader;
use Composer\Autoload\ClassMapGenerator;
use RuntimeException;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\ComposerResource;

class ClassFinder
{
    const WILDCARDS = [
        '**' => '.*',
        '*' => '\\w*',
        '+' => '(?-i:([A-Z][a-z0-9]+|[A-Z]+)_?)',
    ];

    private ClassLoader $classLoader;
    private ?ConfigCache $configCache;
    private ?ClassMapGenerator $classMapGenerator;

    private ?array $classList;
    
    public function __construct(
        ClassLoader $classLoader,
        ConfigCache $configCache = null,
        ClassMapGenerator $classMapGenerator = null
    ) {
        $this->classLoader = $classLoader;
        $this->configCache = $configCache;
        $this->classMapGenerator = $classMapGenerator;
    }

    public function getClassList()
    {
        $this->classList ??= $this->loadClassList();

        if (isset($this->classList)) {
            return $this->classList;
        }

        $classes[] = $this->classLoader->getClassMap();
        
        if ($this->classLoader->isClassMapAuthoritative() === false) {
            $this->classMapGenerator ??= new ClassMapGenerator();

            foreach ($this->classLoader->getPrefixesPsr4() as $namespace => $dirs) {
                foreach ($dirs as $dir) {
                    $classes[] = $this->classMapGenerator->createMap($dir, null, null, $namespace, 'psr-4');
                }
            }

            $dirs = $this->classLoader->getFallbackDirsPsr4();
            foreach ($dirs as $dir) {
                $classes[] = $this->classMapGenerator->createMap($dir, null, null, null, 'psr-4');
            }
            
            $dirs = $this->classLoader->getPrefixes();
            foreach ($this->classLoader->getPrefixes() as $namespace => $dirs) {
                foreach ($dirs as $dir) {
                    $classes[] = $this->classMapGenerator->createMap($dir, null, null, $namespace, 'psr-0');
                }
            }

            $dirs = $this->classLoader->getFallbackDirs();
            foreach ($dirs as $dir) {
                $classes[] = $this->classMapGenerator->createMap($dir, null, null, null, 'psr-0');
            }
        }

        $this->classList = \array_keys(\array_merge(...$classes));

        $this->dumpClassList($this->classList);

        return $this->classList;
    }

    protected function loadClassList(): ?array
    {
        if (!isset($this->configCache)) {
            return null;
        }

        if (!$this->configCache->isFresh()) {
            return null;
        }

        $loader = new class() {
            public function __invoke($file)
            {
                return include $file;
            }
        };
        $value = $loader($this->configCache->getPath());

        if (!\is_array($value)) {
            throw new RuntimeException(\sprintf(
                'Dump file "%s" does not return array as expected.',
                $this->configCache->getPath()
            ));
        }

        return $value;
    }

    protected function dumpClassList(
        array $classList
    ): void {
        if (!isset($this->configCache)) {
            return;
        }

        $dump = \implode("\n\n", [
            '<?php',
            '/* This file was auto-generated by ' . static::class . ' */',
            'return ' . \var_export($classList, true) . ';',
        ]) . "\n";

        $this->configCache->write($dump, [new ComposerResource()]);
    }

    public function findClasses(
        string $search,
        array $classes = null
    ): array {
        $pattern = '/' . $this->getRegexpPattern($search) . '/i';

        $classes = \preg_grep($pattern, $classes ?? $this->getClassList());

        return $classes;
    }

    public function findBestMatch(
        string $search,
        array $classes = null
    ): ?string {
        $pattern = '/' . $this->getRegexpPattern($search) . '(?:Entity)?$/i';

        $classes = \preg_grep($pattern, $classes ?? $this->getClassList());

        return \count($classes) === 1 ? \reset($classes) : null;
    }

    private function getRegexpPattern(
        string $search,
        string $delim = '/'
    ): string {
        $search = \strtr($search, '/', '\\');
        $search = \preg_split(
            '/(' . \implode('|', \array_map(
                fn($w) => \preg_quote($w, $delim),
                \array_keys($this::WILDCARDS)
            )) . ')/',
            $search,
            -1,
            \PREG_SPLIT_DELIM_CAPTURE
        );
        $search = \array_map(fn($p) => $this::WILDCARDS[$p] ?? \preg_quote($p, $delim), $search);
        $search = \implode('', $search);

        return $search;
    }
}
