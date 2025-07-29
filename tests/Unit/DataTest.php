<?php

declare(strict_types=1);

namespace Webgraphe\PhluxTests\Unit;

use Closure;
use DateTime;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use Webgraphe\Phlux\Attributes\ItemPrototype;
use Webgraphe\Phlux\Attributes\ItemType;
use Webgraphe\Phlux\Attributes\Present;
use Webgraphe\Phlux\Data;
use Webgraphe\Phlux\Exceptions\UnknownClassException;
use Webgraphe\Phlux\Exceptions\UnsupportedClassException;
use Webgraphe\Phlux\Exceptions\UnsupportedPropertyTypeException;
use Webgraphe\Phlux\Meta;
use Webgraphe\PhluxTests\Dummies;

#[CoversClass(Data::class)]
#[CoversClass(Meta::class)]
#[CoversClass(ItemPrototype::class)]
#[CoversClass(ItemType::class)]
#[CoversClass(Present::class)]
class DataTest extends UnitTestCase
{
    private ?Closure $listener = null;

    protected function setUp(): void
    {
        Meta::on(
            Meta::SIGNAL_EXCEPTION,
            $this->listener ??= static fn(Meta $meta, string $signal, Exception $e) => throw $e,
        );
    }

    protected function tearDown(): void
    {
        Meta::off(Meta::SIGNAL_EXCEPTION, $this->listener);
    }

    public function testIdentity(): void
    {
        $dto = new Dummies\IdentityData();
        self::assertEquals([], $dto->toArray());
    }

    /**
     * @throws Exception
     */
    public function testScalars(): void
    {
        $dto = new Dummies\TestData(
            $innerDto = new Dummies\TestData(
                json_decode(
                    json_encode(
                        $data = [
                            'name' => 'test',
                            'int' => 42,
                            'bool' => true,
                            'float' => M_PI,
                            'nullableString' => 'hello',
                        ],
                    ),
                ),
            ),
        );
        self::assertEquals('test', $dto->name);
        self::assertEquals(42, $dto->int);
        self::assertTrue($dto->bool);
        self::assertEquals(M_PI, $dto->float);
        self::assertEquals('hello', $dto->nullableString);
        self::assertFalse(isset($dto->strings));
        self::assertFalse(isset($dto->nullableStringMap));
        self::assertFalse(isset($dto->stringsArray));
        self::assertFalse(isset($dto->data));
        $expected = [...$data, 'array' => [], 'object' => null];
        self::assertEquals($expected, $dto->toArray());
        self::assertEquals($expected, $innerDto->toArray());
    }

    /**
     * @throws Exception
     */
    public function testComposites(): void
    {
        $dto = new Dummies\TestData(
            $innerDto = new Dummies\TestData(
                json_decode(
                    json_encode(
                        $data = [
                            'strings' => ['foo', 'bar'],
                            'nullableStringMap' => ['hello' => 'world'],
                            'stringsArray' => [['a', 'b'], ['c', 'd']],
                            'data' => ['int' => 42],
                            'dateTimeImmutable' => '2025-07-27 11:57:23 America/Montreal',
                            'dateTimeInterface' => '2025-07-27 11:57:24 America/Montreal',
                            'yesNoMaybeEnum' => Dummies\YesNoMaybeEnum::NO->value,
                            'oneTwoThreeEnum' => Dummies\OneTwoThreeEnum::THREE->value,
                        ],
                    ),
                ),
            ),
        );
        self::assertFalse(isset($dto->name));
        self::assertEquals(0, $dto->int);
        self::assertFalse($dto->bool);
        self::assertEquals(0.0, $dto->float);
        self::assertNull($dto->nullableString);
        self::assertEquals(['foo', 'bar'], $dto->strings);
        self::assertEquals((object)['hello' => 'world'], $dto->nullableStringMap);
        self::assertEquals([['a', 'b'], ['c', 'd']], $dto->stringsArray);
        self::assertEquals(new Dummies\TestData(['int' => 42]), $dto->data);
        self::assertEquals(
            $expected = [
                ...$data,
                'data' => [
                    'nullableString' => null,
                    'bool' => false,
                    'int' => 42,
                    'float' => 0.0,
                    'array' => [],
                    'object' => null,
                ],
                'nullableString' => null,
                'bool' => false,
                'int' => 0,
                'float' => 0.0,
                'array' => [],
                'object' => null,
            ],
            $dto->toArray(),
        );
        self::assertEquals($expected, $innerDto->toArray());
    }

    /**
     * @throws Exception
     */
    public function testJson(): void
    {
        $json = json_encode($dto = new Dummies\TestData(null));
        $fromJson = new Dummies\TestData(json_decode($json));
        self::assertEquals($dto->toArray(), $fromJson->toArray());
    }

    public function testLazy(): void
    {
        $dto = Dummies\TestData::lazy(['data' => ['int' => 42]]);
        $reflection = Dummies\TestData::meta()->reflectionClass();
        self::assertTrue($reflection->isUninitializedLazyObject($dto));
        $data = $dto->data;
        self::assertFalse($reflection->isUninitializedLazyObject($dto));

        self::assertTrue($reflection->isUninitializedLazyObject($data));
        self::assertEquals(42, $data->int);
        self::assertFalse($reflection->isUninitializedLazyObject($data));
    }

    public function testUnion(): void
    {
        $this->expectExceptionObject(new UnsupportedPropertyTypeException('number'));

        new Dummies\UnionData();
    }

    public function testUndefinedClass(): void
    {
        /** @noinspection PhpUndefinedClassInspection */
        $this->expectExceptionObject(new UnknownClassException(Dummies\Unknown::class));

        new Dummies\UnknownClassData();
    }

    public function testUnsupportedClass(): void
    {
        /** @noinspection PhpUndefinedClassInspection */
        $this->expectExceptionObject(new UnsupportedClassException(DateTime::class));

        new Dummies\UnsupportedClassData();
    }

    public function testUnsupportedComposite(): void
    {
        /** @noinspection PhpUndefinedClassInspection */
        $this->expectExceptionObject(new UnsupportedClassException(DateTime::class));

        new Dummies\UnsupportedCompositeData();
    }
}
