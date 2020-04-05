<?php
namespace nextdev\AdrBundle\Util;

use ReflectionMethod;
use ReflectionProperty;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Composer\Autoload\ClassLoader;
use Composer\Autoload\ClassMapGenerator;
use RuntimeException;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\ComposerResource;

class ClassFinderTest extends TestCase
{
    protected $classListFixture = [
        'App\Foo\Bar',
        'App\Foo\Baz',
        'App\Entity\FooEntity',
        'App\Entity\BarEntity',
        'App\Handler\FooHandler',
        'App\Deeper\Name\FooBar',
        'Acme\Bundle\Bundle',
        'Acme\Bundle\FooBundle',
        'Acme\Bundle\BarBundle',
        'Acme\Bundle\BazBundle',
        'Acme\Bundle\FooBarBundle',
    ];

    public function provideFindClasses(): array
    {
        return [
            [
                'foo',
                [
                    'App\Foo\Bar',
                    'App\Foo\Baz',
                    'App\Entity\FooEntity',
                    'App\Handler\FooHandler',
                    'App\Deeper\Name\FooBar',
                    'Acme\Bundle\FooBundle',
                    'Acme\Bundle\FooBarBundle',
                ],
            ],
            [
                '/+bundle',
                [
                    'Acme\Bundle\FooBundle',
                    'Acme\Bundle\BarBundle',
                    'Acme\Bundle\BazBundle',
                ],
            ],
            [
                'app/*/foo',
                [
                    'App\Entity\FooEntity',
                    'App\Handler\FooHandler',
                ],
            ],
            [
                'app/**/foo',
                [
                    'App\Entity\FooEntity',
                    'App\Handler\FooHandler',
                    'App\Deeper\Name\FooBar',
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideFindClasses
     */
    public function testFindClasses(
        $search,
        $expected
    ): void {
        $finder = new ClassFinder();
        $classList = new ReflectionProperty(ClassFinder::class, 'classList');
        $classList->setAccessible(true);
        $classList->setValue($finder, $this->classListFixture);

        $this->assertEquals($expected, \array_values($finder->findClasses($search)));
    }

    public function provideFindBestMatch(): array
    {
        return [
            [
                'foo',
                'App\Entity\FooEntity',
            ],
            [
                'bar',
                null,
            ],
            [
                'baz',
                'App\Foo\Baz',
            ],
        ];
    }

    /**
     * @dataProvider provideFindBestMatch
     */
    public function testFindBestMatch(
        $search,
        $expected
    ): void {
        $finder = new ClassFinder();
        $classList = new ReflectionProperty(ClassFinder::class, 'classList');
        $classList->setAccessible(true);
        $classList->setValue($finder, $this->classListFixture);

        $this->assertEquals($expected, $finder->findBestMatch($search));
    }

    public function testGetClassListFromLoader()
    {
        $loader = $this->createMock(ClassLoader::class);
        $loader->method('getClassMap')->willReturn($map[] = [
            'App\Foo' => 'some/path/to.php'
        ]);
        $loader->method('isClassMapAuthoritative')->willReturn(false);
        $loader->method('getPrefixesPsr4')->willReturn([
            'Foo\\' => ['some/path/to/foo/'],
        ]);
        $loader->method('getFallbackDirsPsr4')->willReturn([
            'some/path/to/fallback/',
        ]);
        $loader->method('getPrefixes')->willReturn([
            'Bar\\' => ['some/path/to/bar/'],
        ]);
        $loader->method('getFallbackDirs')->willReturn([
            'some/path/to/anotherfallback/',
        ]);

        $mapGenerator = new class() extends ClassMapGenerator {
            public static array $expected;
            public static array $willReturn;
            public static $assertEquals;
            public static int $call;
            public static function createMap($path, $blacklist = null, $io = null, $namespace = null, $autoloadType = null, &$scannedFiles = null)
            {
                $call = static::$call++;
                (static::$assertEquals)(static::$expected[$call], \func_get_args(), $call);
                return static::$willReturn[$call];
            }
        };
        $mapGenerator::$expected = [
            ['some/path/to/foo/', null, null, 'Foo\\', 'psr-4'],
            ['some/path/to/fallback/', null, null, null, 'psr-4'],
            ['some/path/to/bar/', null, null, 'Bar\\', 'psr-0'],
            ['some/path/to/anotherfallback/', null, null, null, 'psr-0'],
        ];
        $mapGenerator::$willReturn = [
            $map[] = [
                'Foo\Bar' => 'the/real/path',
                'Foo\Baz' => 'the/real/path',
            ],
            $map[] = [
                'Vendor\Project\Class' => 'the/real/path',
            ],
            $map[] = [
                'Bar\\Baz' => 'the/real/path',
            ],
            $map[] = [
                'Foo\Foo' => 'the/real/path',
            ],
        ];
        $mapGenerator::$assertEquals = function ($expected, $actual, $i) {
            $this->assertEquals($expected, $actual, \sprintf(
                'Call %d for ClassMapGenerator::createMap()',
                $i
            ));
        };
        $mapGenerator::$call = 0;

        $finder = new ClassFinder([$loader], null, $mapGenerator);

        $this->assertEquals(\array_keys(\array_merge(...$map)), $finder->getClassList());
    }

    public function testCacheClassList()
    {
        $classes = ['foo', 'bar', 'baz'];

        $vfs = vfsStream::setup();
        $cache = new ConfigCache($vfs->url() . '/test.php', true);
        $finder = new ClassFinder([], $cache);

        $dumpMethod = new ReflectionMethod(ClassFinder::class, 'dumpClassList');
        $dumpMethod->setAccessible(true);
        $loadMethod = new ReflectionMethod(ClassFinder::class, 'loadClassList');
        $loadMethod->setAccessible(true);

        $this->assertEquals(null, $loadMethod->invoke($finder));

        $dumpMethod->invoke($finder, $classes);
        
        $this->assertEquals($classes, $loadMethod->invoke($finder));
    }

    public function testExceptionOnCompromisedDumpfile()
    {
        $vfs = vfsStream::setup();
        $cache = new ConfigCache($vfs->url() . '/test.php', true);
        $finder = new ClassFinder([], $cache);

        $loadMethod = new ReflectionMethod(ClassFinder::class, 'loadClassList');
        $loadMethod->setAccessible(true);

        $cache->write('<?php return;', [new ComposerResource]);
        
        $this->expectException(RuntimeException::class);
        $loadMethod->invoke($finder);
    }
}
