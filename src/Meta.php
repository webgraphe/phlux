<?php

declare(strict_types=1);

namespace Webgraphe\Phlux;

use BackedEnum;
use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Generator;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use Webgraphe\Phlux\Attributes\ItemPrototype;
use Webgraphe\Phlux\Attributes\ItemType;
use Webgraphe\Phlux\Attributes\Present;
use Webgraphe\Phlux\Concerns\EmitsEvents;
use Webgraphe\Phlux\Contracts\EventEmitter;
use Webgraphe\Phlux\Contracts\Unmarshaller;
use Webgraphe\Phlux\Exceptions\PresentException;
use Webgraphe\Phlux\Exceptions\UnknownClassException;
use Webgraphe\Phlux\Exceptions\UnsupportedClassException;
use Webgraphe\Phlux\Exceptions\UnsupportedPropertyTypeException;

final class Meta implements EventEmitter
{
    use EmitsEvents;

    public const string SIGNAL_EXCEPTION = 'exception';

    /** @var array<class-string<Data>, ReflectionClass> */
    private static array $reflections = [];
    private static array $instances = [];
    private static int $lazy = 0;

    /** @var array<string, Closure> Associated by property name */
    private array $unmarshallers = [];

    private function __construct(public readonly string $class) {}

    public static function lazy(callable $callable, mixed ...$args): mixed
    {
        try {
            ++self::$lazy;

            return $callable(...$args);
        } finally {
            --self::$lazy;
        }
    }

    /**
     * @param class-string<Data> $class
     */
    public static function get(string $class): self
    {
        return self::$instances[$class] ??= new self($class);
    }

    private static function isComposite(ReflectionNamedType $type): bool
    {
        return in_array($type->getName(), ['array', 'object']);
    }

    public function vars(Data $data): Generator
    {
        yield from get_object_vars($data);
    }

    public function marshal(Data $data): Generator
    {
        foreach ($this->vars($data) as $name => $value) {
            yield $name => self::arrayize($value);
        }
    }

    /**
     * @return Generator<Closure|Unmarshaller>
     */
    public function unmarshallers(): Generator
    {
        foreach ($this->reflectionClass()->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            try {
                yield $property->getName() => $this->propertyUnmarshaller($property);
            } catch (Exception $e) {
                self::emit($this, self::SIGNAL_EXCEPTION, $e);
            }
        }
    }

    /**
     * @throws ReflectionException
     * @throws UnknownClassException
     * @throws UnsupportedClassException
     * @throws UnsupportedPropertyTypeException
     */
    private function propertyUnmarshaller(ReflectionProperty $property): Closure
    {
        if (!isset($this->unmarshallers[$property->getName()])) {
            $name = $property->getName();
            $typeUnmarshaller = $this->typeUnmarshaller(
                $property->getType(),
                "$this->class::$name",
                ($property->getAttributes(ItemPrototype::class)[0] ?? null)?->newInstance(),
                ($property->getAttributes(ItemType::class)[0] ?? null)?->newInstance(),
                $property->getDeclaringClass()->getName(),
            );

            $this->unmarshallers[$property->getName()] = static function (mixed $value = null) use (
                $property,
                $typeUnmarshaller,
            ): mixed {
                if (!func_num_args() && $property->getAttributes(Present::class)) {
                    throw new PresentException();
                }

                return $typeUnmarshaller($value);
            };
        }

        return $this->unmarshallers[$property->getName()];
    }

    /**
     * @throws ReflectionException
     * @throws UnknownClassException
     * @throws UnsupportedClassException
     * @throws UnsupportedPropertyTypeException
     */
    private function typeUnmarshaller(
        ReflectionType $type,
        string $stub,
        ?ItemPrototype $itemPrototype = null,
        ?ItemType $itemType = null,
        ?string $declaringClass = null,
    ): Closure {
        if (!($type instanceof ReflectionNamedType)) {
            throw new UnsupportedPropertyTypeException($stub);
        }

        return match (true) {
            !$type->isBuiltin() => self::classUnmarshaller(
                self::supportedClass($type->getName(), $declaringClass),
                $type,
                $stub,
            ),
            self::isComposite($type) => $this->compositeUnmarshaller($itemPrototype, $itemType, $type, $stub),
            default => self::builtInMarshaller($type),
        };
    }

    /**
     * @return class-string<Data>
     * @throws UnknownClassException
     * @throws UnsupportedClassException
     */
    private static function supportedClass(string $class, ?string $declaringClass = null): string
    {
        $declaringClass ??= $class;
        if ('self' === $class) {
            $class = $declaringClass;
        }

        if (!class_exists($class) && !interface_exists($class)) {
            throw new UnknownClassException($class);
        }

        if (!is_a($class, Data::class, true)
            && (Data::class !== $class)
            && !is_a($class, DateTimeImmutable::class, true)
            && (DateTimeInterface::class !== $class)
            && !is_a($class, BackedEnum::class, true)
            && (BackedEnum::class !== $class)
        ) {
            throw new UnsupportedClassException($class);
        }

        return $class;
    }

    /**
     * @throws UnsupportedPropertyTypeException
     */
    private static function classUnmarshaller(string $class, ReflectionType $type, string $stub): Closure
    {
        $callable = match (true) {
            is_a($class, Data::class, true) => static fn(mixed $value) => self::$lazy
                ? $class::lazy($value)
                : new $class($value),
            is_a($class, DateTimeImmutable::class, true) => static fn(mixed $value)
                => $value instanceof DateTimeImmutable ? $value : new $class($value ?? 'now'),
            DateTimeInterface::class === $class => static fn(mixed $value)
                => $value instanceof DateTimeInterface
                ? DateTimeImmutable::createFromFormat('U.u', $value->format('U.u'))->setTimezone($value->getTimezone())
                : new DateTimeImmutable($value ?? 'now'),
            is_subclass_of($class, BackedEnum::class) => static fn(mixed $value)
                => $value instanceof $class
                ? $value
                : (isset($value) ? $class::tryFrom($value) : null) ?? $class::cases()[0] ?? null,
            // @codeCoverageIgnoreStart
            default => throw new UnsupportedPropertyTypeException($stub),
            // @codeCoverageIgnoreEnd
        };

        $allowsNull = $type->allowsNull();

        return function (mixed $value) use ($allowsNull, $callable): mixed {
            return (null === $value) && $allowsNull ? null : $callable($value);
        };
    }

    private static function builtInMarshaller(ReflectionNamedType $type): Closure
    {
        $allowsNull = $type->allowsNull();
        $name = $type->getName();

        return function (mixed $value) use ($allowsNull, $name): mixed {
            if ($allowsNull && null === $value) {
                return null;
            }

            settype($value, $name);

            return $value;
        };
    }

    public function reflectionClass(): ReflectionClass
    {
        return self::$reflections[$this->class] ??= (static fn(string $c) => new ReflectionClass($c))($this->class);
    }

    private static function arrayize(mixed $value): mixed
    {
        return match (true) {
            $value instanceof Data => $value->toArray() ?: (object)[],
            $value instanceof BackedEnum => $value->value,
            $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s e'),
            is_array($value) => array_map(self::arrayize(...), $value),
            is_object($value) => array_map(self::arrayize(...), get_object_vars($value)) ?: (object)[],
            default => $value,
        };
    }

    /**
     * @throws ReflectionException
     * @throws UnknownClassException
     * @throws UnsupportedClassException
     * @throws UnsupportedPropertyTypeException
     */
    private function compositeUnmarshaller(
        ?ItemPrototype $itemPrototype,
        ?ItemType $itemType,
        ReflectionNamedType $type,
        string $stub,
    ): Closure {
        $itemUnmarshaller = match (false) {
            !$itemPrototype => $this->propertyUnmarshaller(
                $this->reflectionClass()->getProperty($itemPrototype->propertyName),
            ),
            !$itemType => $this->typeUnmarshaller($itemType->asReflectionNamedType(), $stub),
            default => static fn(mixed $value): mixed => $value,
        };

        $allowsNull = $type->allowsNull();
        $name = $type->getName();

        return function (mixed $value) use ($allowsNull, $itemUnmarshaller, $name) {
            if ($allowsNull && null === $value) {
                return null;
            }

            $value = array_map($itemUnmarshaller, is_object($value) ? get_object_vars($value) : ($value ?? []));
            settype($value, $name);

            return 'array' === $name ? array_values($value) : $value;
        };
    }
}
