<?php
namespace nextdev\AdrBundle\Util;

use ReflectionProperty;
use PHPUnit\Framework\TestCase;
use Composer\Autoload\ClassLoader;
use LogicException;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\ErrorHandler\DebugClassLoader;

class ClassFinderFactoryTest extends TestCase
{
    public function testCreateClassFinder()
    {
        $loader = new ClassLoader();
        $wrapLoader = new DebugClassLoader([$loader, 'loadClass']);
        $cacheFile = 'foo';

        $factory = new ClassFinderFactory(
            $this->getKernel($cacheFile),
            function () use ($wrapLoader) {
                return [[$wrapLoader, 'loadClass']];
            }
        );

        $finder = $factory();

        $this->assertInstanceOf(ClassFinder::class, $finder);

        $loaderProp = new ReflectionProperty(ClassFinder::class, 'classLoader');
        $loaderProp->setAccessible(true);
        $this->assertEquals($loader, $loaderProp->getValue($finder));

        $cacheProp = new ReflectionProperty(ClassFinder::class, 'configCache');
        $cacheProp->setAccessible(true);
        /** @var ConfigCache */
        $cache = $cacheProp->getValue($finder);
        $this->assertInstanceOf(ConfigCache::class, $cache);
        $this->assertEquals($cacheFile, $cache->getPath());
    }

    public function testExceptionOnInvalidAutoloader()
    {
        $factory = new ClassFinderFactory(
            $this->getKernel('foo'),
            fn() => false
        );

        $this->expectException(LogicException::class);

        $factory();
    }

    protected function getKernel(
        string $classesCacheFile,
        bool $debug = true
    ): KernelInterface {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('getParameter')
            ->with(ClassFinderFactory::PARAM_CACHE_CLASSES)
            ->willReturn($classesCacheFile);

        $kernel = $this->createMock(KernelInterface::class);
        $kernel->method('isDebug')->willReturn($debug);
        $kernel->method('getContainer')->willReturn($container);

        /** @var KernelInterface $kernel */
        return $kernel;
    }
}
