<?php
namespace Pitch\AdrBundle\Util;

use Composer\Autoload\ClassLoader;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\ErrorHandler\DebugClassLoader;

class ClassFinderFactory
{
    const PARAM_CACHE_CLASSES = 'pitch_adr.classfinder.cache';

    private KernelInterface $kernel;
    private $autoloadStack;

    public function __construct(
        KernelInterface $kernel,
        callable $autoloadStack = null
    ) {
        $this->kernel = $kernel;
        $this->autoloadStack = $autoloadStack ?? 'spl_autoload_functions';
    }

    public function __invoke()
    {
        return new ClassFinder(
            $this->getClassLoadersFromAutoload(),
            $this->getCacheFromKernel()
        );
    }

    /**
     * @return ClassLoader[]
     */
    protected function getClassLoadersFromAutoload(): array
    {
        $loaders = [];
        
        $autoloadStack = ($this->autoloadStack)();

        if (\is_array($autoloadStack)) {
            foreach ($autoloadStack as $callback) {
                if (\is_array($callback) && $callback[0] instanceof DebugClassLoader) {
                    $callback = $callback[0]->getClassLoader();
                }

                if (\is_array($callback) && $callback[0] instanceof ClassLoader) {
                    $loaders[] = $callback[0];
                }
            }
        }

        return $loaders;
    }

    protected function getCacheFromKernel(): ConfigCache
    {
        return new ConfigCache(
            $this->kernel->getContainer()->getParameter($this::PARAM_CACHE_CLASSES),
            $this->kernel->isDebug()
        );
    }
}
