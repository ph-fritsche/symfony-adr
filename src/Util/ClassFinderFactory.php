<?php
namespace nextdev\AdrBundle\Util;

use LogicException;
use Composer\Autoload\ClassLoader;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\ErrorHandler\DebugClassLoader;

class ClassFinderFactory
{
    const PARAM_CACHE_CLASSES = 'nextdev_adr.classfinder.cache';

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
            $this->getClassLoaderFromAutoload(),
            $this->getCacheFromKernel()
        );
    }

    protected function getClassLoaderFromAutoload(): ClassLoader
    {
        $autoloadStack = ($this->autoloadStack)();

        if (\is_array($autoloadStack)) {
            $classLoader = \reset($autoloadStack);
            if (\is_callable($classLoader) && \is_array($classLoader)) {
                $classLoader = $classLoader[0];
            }

            if ($classLoader instanceof DebugClassLoader) {
                $classLoader = $classLoader->getClassLoader();
                if (\is_array($classLoader)) {
                    $classLoader = $classLoader[0];
                }
            }
        } else {
            $classLoader = 'spl_autoload';
        }

        if (!$classLoader instanceof ClassLoader) {
            throw new LogicException(\sprintf(
                'Autoloader %s is not supported for discovering classes. %s required.',
                \is_object($classLoader) ? \get_class($classLoader) : (string) $classLoader,
                ClassLoader::class
            ));
        }

        return $classLoader;
    }

    protected function getCacheFromKernel(): ConfigCache
    {
        return new ConfigCache(
            $this->kernel->getContainer()->getParameter($this::PARAM_CACHE_CLASSES),
            $this->kernel->isDebug()
        );
    }
}
