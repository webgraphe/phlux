<?php

declare(strict_types=1);

namespace Webgraphe\PhluxTests\Unit;

use Carbon\Carbon;
use Closure;
use DateTime;
use Error;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use Webgraphe\Phlux\Attributes\Discriminator;
use Webgraphe\Phlux\Attributes\ItemPrototype;
use Webgraphe\Phlux\Attributes\ItemType;
use Webgraphe\Phlux\Attributes\Present;
use Webgraphe\Phlux\Contracts\DataTransferObject;
use Webgraphe\Phlux\Data;
use Webgraphe\Phlux\Exceptions\DiscriminatorException;
use Webgraphe\Phlux\Exceptions\InvalidNamespaceDiscriminatorException;
use Webgraphe\Phlux\Exceptions\MissingDataDiscriminatorException;
use Webgraphe\Phlux\Exceptions\UndefinedClassDiscriminatorException;
use Webgraphe\Phlux\Exceptions\UnknownClassException;
use Webgraphe\Phlux\Exceptions\UnmappedClassDiscriminatorException;
use Webgraphe\Phlux\Exceptions\UnmappedValueDiscriminatorException;
use Webgraphe\Phlux\Exceptions\UnsupportedClassException;
use Webgraphe\Phlux\Exceptions\UnsupportedPropertyTypeException;
use Webgraphe\Phlux\Meta;
use Webgraphe\PhluxTests\Dummies;
use Webgraphe\PhluxTests\Dummies\Bare;
use Webgraphe\PhluxTests\Dummies\CarbonData;
use Webgraphe\PhluxTests\Dummies\Discriminated\AbstractAbstractMappedData;

#[CoversClass(Data::class)]
#[CoversClass(Meta::class)]
#[CoversClass(Discriminator::class)]
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
        $dto = new Dummies\IdentityData('');
        self::assertEquals(['name' => ''], $dto->toArray());
        $dto = Dummies\IdentityData::instantiate();
        self::assertEquals([], $dto->toArray());
        self::assertFalse(isset($dto->name));
    }

    /**
     * @throws Exception
     */
    public function testScalars(): Dummies\TestData
    {
        $dto = Dummies\TestData::from(
            $innerDto = Dummies\TestData::from(
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

        return $dto;
    }

    #[Depends('testScalars')]
    public function testArrayAccess(Dummies\TestData $dto): void
    {
        self::assertTrue(isset($dto['name']));
        self::assertEquals('test', $dto['name']);
        self::assertEquals(42, $dto['int']);
        self::assertTrue($dto['bool']);
        self::assertEquals(M_PI, $dto['float']);
        self::assertEquals('hello', $dto['nullableString']);
        self::assertFalse(isset($dto['strings']));
        self::assertFalse(isset($dto['nullableStringMap']));
        self::assertFalse(isset($dto['stringsArray']));
        self::assertFalse(isset($dto['data']));
    }

    #[Depends('testScalars')]
    public function testOffsetExists(Dummies\TestData $dto): void
    {
        self::assertFalse(isset($dto['not defined']));
    }

    #[Depends('testScalars')]
    public function testOffsetSet(Dummies\TestData $dto): void
    {
        $this->expectException(Error::class);

        $dto['name'] = 'test';
    }

    #[Depends('testScalars')]
    public function testOffsetUnset(Dummies\TestData $dto): void
    {
        $this->expectException(Error::class);

        unset($dto['name']);
    }

    /**
     * @throws Exception
     */
    public function testComposites(): void
    {
        $dto = Dummies\TestData::from(
            $innerDto = Dummies\TestData::from(
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
        self::assertEquals(Dummies\TestData::from(['int' => 42]), $dto->data);
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

    public function testCarbon(): void
    {
        $now = Carbon::now()->microsecond(0);
        $data = CarbonData::instantiate(datetime: $now->toAtomString());

        self::assertTrue($data->datetime->eq($now));
    }

    /**
     * @throws Exception
     */
    public function testJson(): void
    {
        $json = json_encode($dto = Dummies\TestData::from(null));
        $fromJson = Dummies\TestData::from(json_decode($json));
        self::assertEquals($dto->toArray(), $fromJson->toArray());
    }

    public function testLazyInstantiate(): void
    {
        $dto = Dummies\TestData::lazyInstantiate(data: ['int' => 42]);
        self::assertTrue(Data::isLazy($dto));
        $data = $dto->data;
        self::assertFalse(Data::isLazy($dto));

        self::assertTrue(Data::isLazy($data));
        self::assertEquals(42, $data->int);
        self::assertFalse(Data::isLazy($data));
    }

    /**
     * @throws DiscriminatorException
     */
    public function testLazyFrom(): void
    {
        $dto = Dummies\TestData::lazyFrom(['data' => ['int' => 42]]);
        self::assertTrue(Data::isLazy($dto));
        $data = $dto->data;
        self::assertFalse(Data::isLazy($dto));

        self::assertTrue(Data::isLazy($data));
        self::assertEquals(42, $data->int);
        self::assertFalse(Data::isLazy($data));
    }

    public function testUnion(): void
    {
        $this->expectExceptionObject(new UnsupportedPropertyTypeException('number'));

        Dummies\UnionData::instantiate();
    }

    public function testUndefinedClass(): void
    {
        /** @noinspection PhpUndefinedClassInspection */
        $this->expectExceptionObject(new UnknownClassException(Dummies\Unknown::class));

        Dummies\UnknownClassData::instantiate();
    }

    public function testUnsupportedClass(): void
    {
        /** @noinspection PhpUndefinedClassInspection */
        $this->expectExceptionObject(new UnsupportedClassException(DateTime::class));

        Dummies\UnsupportedClassData::instantiate();
    }

    public function testUnsupportedComposite(): void
    {
        /** @noinspection PhpUndefinedClassInspection */
        $this->expectExceptionObject(new UnsupportedClassException(DateTime::class));

        Dummies\UnsupportedCompositeData::instantiate();
    }

    /**
     * @throws DiscriminatorException
     */
    public function testFromDiscriminatorMapped(): void
    {
        self::assertInstanceOf(
            Dummies\Discriminated\MappedLeftData::class,
            Dummies\Discriminated\AbstractMappedData::from(['type' => Dummies\Discriminated\AbstractMappedData::left])
        );
        self::assertInstanceOf(
            Dummies\Discriminated\MappedRightData::class,
            Dummies\Discriminated\AbstractMappedData::from(['type' => Dummies\Discriminated\AbstractMappedData::right])
        );
    }

    /**
     * @throws DiscriminatorException
     */
    public function testFromDiscriminatorUnmapped(): void
    {
        self::assertInstanceOf(
            Dummies\Discriminated\UnmappedLeftData::class,
            Dummies\Discriminated\AbstractUnmappedData::from(
                ['type' => Dummies\Discriminated\AbstractUnmappedData::UnmappedLeftData],
            ),
        );
        self::assertInstanceOf(
            Dummies\Discriminated\UnmappedRightData::class,
            Dummies\Discriminated\AbstractUnmappedData::from(
                ['type' => Dummies\Discriminated\AbstractUnmappedData::UnmappedRightData],
            ),
        );
    }

    /**
     * @throws DiscriminatorException
     */
    public function testDiscriminatedProperties(): void
    {
        $dto = Dummies\DiscriminatedData::from(
            [
                'mapped' => [
                    'type' => Dummies\Discriminated\AbstractMappedData::left,
                ],
                'unmapped' => [
                    'type' => Dummies\Discriminated\AbstractUnmappedData::UnmappedRightData,
                ],
            ]
        );
        self::assertInstanceOf(Dummies\Discriminated\MappedLeftData::class, $dto->mapped);
        self::assertInstanceOf(Dummies\Discriminated\UnmappedRightData::class, $dto->unmapped);
    }

    /**
     * @throws DiscriminatorException
     */
    public function testMissingDiscriminatorData(): void
    {
        $stub = Dummies\Discriminated\AbstractMappedData::class . '::$type';
        $this->expectExceptionObject(new MissingDataDiscriminatorException($stub));
        Dummies\Discriminated\AbstractMappedData::from([]);
    }

    /**
     * @throws DiscriminatorException
     */
    public function testUnmappedDiscriminatorData(): void
    {
        $stub = Dummies\Discriminated\AbstractMappedData::class . '::$type';
        $discriminator = 'unmapped';
        $this->expectExceptionObject(new UnmappedValueDiscriminatorException("$stub=$discriminator"));
        Dummies\Discriminated\AbstractMappedData::from(['type' => $discriminator]);
    }

    /**
     * @throws DiscriminatorException
     */
    public function testMappedDiscriminatorUndefinedClass(): void
    {
        /** @noinspection PhpUndefinedClassInspection */
        $class = Dummies\Discriminated\Undefined::class;
        $discriminator = Dummies\Discriminated\AbstractMappedData::undefined;
        $this->expectExceptionObject(new UndefinedClassDiscriminatorException($class));
        Dummies\Discriminated\AbstractMappedData::from(['type' => $discriminator]);
    }

    /**
     * @throws DiscriminatorException
     */
    public function testMappedDiscriminatorUnmappedClass(): void
    {
        /** @noinspection PhpUndefinedClassInspection */
        $stub = Dummies\Discriminated\AbstractMappedData::class . '::$type';
        $discriminator = 'unmapped';
        $this->expectExceptionObject(new UnmappedValueDiscriminatorException("$stub=$discriminator"));
        Dummies\Discriminated\AbstractMappedData::from(['type' => $discriminator]);
    }

    /**
     * @throws DiscriminatorException
     */
    public function testUnmappedDiscriminatorUnmappedClass(): void
    {
        /** @noinspection PhpUndefinedClassInspection */
        $class = Dummies\Discriminated\Undefined::class;
        $discriminator = 'Undefined';
        $this->expectExceptionObject(new UndefinedClassDiscriminatorException($class));
        Dummies\Discriminated\AbstractUnmappedData::from(['type' => $discriminator]);
    }

    public function testUnmappedDiscriminatorClassException(): void
    {
        /** @noinspection PhpUndefinedClassInspection */
        $class = Dummies\Discriminated\MappedUnmappedData::class;
        $this->expectExceptionObject(new UnmappedClassDiscriminatorException($class));
        Dummies\Discriminated\MappedUnmappedData::instantiate();
    }

    public function testNewMappedInstanceIsDiscriminated(): void
    {
        $dto = Dummies\Discriminated\MappedLeftData::instantiate();
        self::assertEquals(Dummies\Discriminated\AbstractMappedData::left, $dto->type);
    }

    public function testNewUnmappedInstanceIsDiscriminated(): void
    {
        $dto = Dummies\Discriminated\UnmappedRightData::instantiate();
        self::assertEquals(Dummies\Discriminated\AbstractUnmappedData::UnmappedRightData, $dto->type);
    }

    public function testNewInvalidNamespaceUnmappedInstance(): void
    {
        $this->expectExceptionObject(
            new InvalidNamespaceDiscriminatorException(Dummies\InvalidNamespaceUnmappedData::class)
        );
        Dummies\InvalidNamespaceUnmappedData::instantiate();
    }

    /**
     * @throws DiscriminatorException
     */
    public function testDiscriminatorOnNonAbstractClass(): void
    {
        $this->expectExceptionObject(new DiscriminatorException('Discriminator MUST be declared on abstract class'));
        Dummies\NonAbstractDiscriminatedData::from(null);
    }

    /** @param class-string<DataTransferObject> $class */
    #[DataProvider('dataProviderInvalidDiscriminatorProperty')]
    public function testInvalidDiscriminatorProperty(string $class): void
    {
        $this->expectExceptionObject(new DiscriminatorException('Discriminator property MUST be a final non-nullable string'));
        $class::from(null);
    }

    /**
     * @throws DiscriminatorException
     */
    public function testDiscriminatorPropertyOnNonAttributedClass(): void
    {
        $this->expectExceptionObject(new DiscriminatorException('Discriminator property MUST be declared on attributed class'));
        AbstractAbstractMappedData::from(null);
    }

    public static function dataProviderInvalidDiscriminatorProperty(): array
    {
        return [
            [
                'class' => Dummies\NonFinalDiscriminatedData::class,
            ],
            [
                'class' => Dummies\NonStringDiscriminatedData::class,
            ],
        ];
    }

    public function testInstantiate(): Dummies\TestData
    {
        $instance = Dummies\TestData::instantiate(name: 'instantiated');
        self::assertEquals('instantiated', $instance->name);

        return $instance;
    }

    public function testBare(): void
    {
        $bare = Bare::instantiate();
        self::assertJsonStringEqualsJsonString('{}', json_encode($bare));
    }

    /**
     * @throws DiscriminatorException
     */
    public function testWith(): void
    {
        $data = [
            'name' => 'test',
            'int' => 42,
            'bool' => true,
            'float' => M_PI,
            'nullableString' => 'hello',
            'array' => [],
            'object' => [],
        ];

        $dto = Dummies\TestData::from($data)->with(int: 137, bool: false);

        self::assertEquals([...$data, 'int' => 137, 'bool' => false], $dto->toArray());
    }
}
