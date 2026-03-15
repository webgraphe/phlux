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
use Webgraphe\Phlux\Attributes\Discriminator;
use Webgraphe\Phlux\Attributes\ItemPrototype;
use Webgraphe\Phlux\Attributes\ItemType;
use Webgraphe\Phlux\Attributes\Present;
use Webgraphe\Phlux\Concerns\EmitsEvents;
use Webgraphe\Phlux\Contracts\DataTransferObject;
use Webgraphe\Phlux\Contracts\EventEmitter;
use Webgraphe\Phlux\Contracts\Unmarshaller;
use Webgraphe\Phlux\Exceptions\DiscriminatorException;
use Webgraphe\Phlux\Exceptions\PresentException;
use Webgraphe\Phlux\Exceptions\UnknownClassException;
use Webgraphe\Phlux\Exceptions\UnsupportedClassException;
use Webgraphe\Phlux\Exceptions\UnsupportedPropertyTypeException;

final class Meta implements EventEmitter
{
    use EmitsEvents;

    public const string SIGNAL_EXCEPTION = 'exception';

    /** @var array<class-string<DataTransferObject>, ReflectionClass> */
    private static array $reflections = [];
    /** @var array<class-string<DataTransferObject>, Discriminator|null> */
    private static array $discriminators = [];
    /** @var array<class-string<DataTransferObject>, self> */
    private static array $instances = [];
    private static int $lazy = 0;

    /** @var array<string, Closure> Associated by property name */
    private array $unmarshallers = [];

    /**
     * @param class-string<DataTransferObject> $class
     */
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

    private function propertyName(ReflectionProperty $property): string
    {
        $className = $property->getDeclaringClass()->getName();

        return is_a($this->class, $className, true)
            ? $property->getName()
            : sprintf('%s::$%s', $className, $property->getName());
    }

    /**
     * @param class-string<DataTransferObject> $class
     */
    public static function get(string $class): self
    {
        return self::$instances[$class] ??= new self($class);
    }

    /**
     * Because Meta is separate from Data, it only returns the public vars
     */
    public function vars(DataTransferObject $data): Generator
    {
        yield from get_object_vars($data);
    }

    public function marshal(DataTransferObject $data): Generator
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

    public function reflectionClass(): ReflectionClass
    {
        return self::$reflections[$this->class]
            ??= (static fn(string $c): ReflectionClass => new ReflectionClass($c))($this->class);
    }

    private function reflectionProperty(string $name): ReflectionProperty
    {
        return (fn() => $this->reflectionClass()->getProperty($name))();
    }

    /**
     * @throws DiscriminatorException
     */
    public function getDiscriminator(): ?Discriminator
    {
        if (!array_key_exists($this->class, self::$discriminators)) {
            $discriminated = $this->reflectionClass()->getAttributes(Discriminator::class)[0] ?? null;
            if ($discriminator = $discriminated?->newInstance()) {
                if (!$this->reflectionClass()->isAbstract()) {
                    throw new DiscriminatorException("Discriminator MUST be declared on abstract class");
                }

                $property = $this->reflectionProperty($discriminator->propertyName);
                if ($property->getDeclaringClass()->getName() !== $this->class) {
                    throw new DiscriminatorException("Discriminator property MUST be declared on attributed class");
                }

                if (!$property->isFinal()
                    || !(($type = $property->getType()) instanceof ReflectionNamedType)
                    || 'string' !== $type->getName()
                    || $type->allowsNull()
                ) {
                    throw new DiscriminatorException("Discriminator property MUST be a final non-nullable string");
                }

                /** @var Discriminator|null $discriminator */
                self::$discriminators[$this->class] = $discriminator;
            } elseif ($parent = array_values(class_parents($this->class))[0] ?? null) {
                self::$discriminators[$this->class] = self::get($parent)->getDiscriminator();
            } else {
                self::$discriminators[$this->class] = null;
            }
        }

        return self::$discriminators[$this->class];
    }

    private static function isCollection(ReflectionNamedType $type): bool
    {
        return in_array($type->getName(), ['array', 'object']);
    }

    /**
     * @throws DiscriminatorException
     * @throws UnknownClassException
     * @throws UnsupportedClassException
     * @throws UnsupportedPropertyTypeException
     */
    private function propertyUnmarshaller(
        ReflectionProperty $property,
        ?ReflectionProperty $compositeProperty = null,
    ): Closure {
        $name = $this->propertyName($property);
        if (isset($this->unmarshallers[$name])) {
            return $this->unmarshallers[$name];
        }

        if ($discriminator = $this->getDiscriminator()) {
            if ($this->propertyName($this->reflectionProperty($discriminator->propertyName)) === $name) {
                $value = $discriminator->resolveValue($this->class, $property->getDeclaringClass()->getName());

                return $this->unmarshallers[$name] = static fn(): string => $value;
            }
        }

        if (!(($type = $property->getType()) instanceof ReflectionNamedType)) {
            throw new UnsupportedPropertyTypeException($name);
        }

        $unmarshaller = match (true) {
            !$type->isBuiltin() => self::classUnmarshaller($property, $compositeProperty),
            self::isCollection($type) => $this->collectionUnmarshaller($property, $compositeProperty),
            default => self::builtInMarshaller($type, $compositeProperty),
        };

        return $this->unmarshallers[$name] = static function (mixed $value = null) use ($property, $unmarshaller): mixed {
            if (!func_num_args() && $property->getAttributes(Present::class)) {
                throw new PresentException();
            }

            return $unmarshaller($value);
        };
    }

    /**
     * @return class-string<DataTransferObject>
     * @throws UnknownClassException
     * @throws UnsupportedClassException
     */
    private static function supportedClass(string $class, ?string $declaringClass): string
    {
        $declaringClass ??= $class;
        if ('self' === $class) {
            // @codeCoverageIgnoreStart
            $class = $declaringClass;
            // @codeCoverageIgnoreEnd
        }

        if (!class_exists($class) && !interface_exists($class)) {
            throw new UnknownClassException($class);
        }

        if (!is_subclass_of($class, DataTransferObject::class)
            && (DataTransferObject::class !== $class)
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
     * @throws UnknownClassException
     * @throws UnsupportedClassException
     * @throws UnsupportedPropertyTypeException
     */
    private function classUnmarshaller(ReflectionProperty $property): Closure
    {
        $type = $property->getType();
        $class = self::supportedClass($type->getName(), $property->getDeclaringClass()->getName());
        $callable = match (true) {
            is_a($class, DataTransferObject::class, true) => static fn(mixed $value)
                => self::$lazy ? $class::lazyFrom($value) : $class::from($value),
            is_a($class, DateTimeImmutable::class, true) => static fn(mixed $value)
                => $value instanceof DateTimeImmutable ? $value : new $class($value ?? 'now'),
            DateTimeInterface::class === $class => static fn(mixed $value)
                => $value instanceof DateTimeInterface
                ? DateTimeImmutable::createFromInterface($value)
                : new DateTimeImmutable($value ?? 'now'),
            is_subclass_of($class, BackedEnum::class) => static fn(mixed $value)
                => $value instanceof $class
                ? $value
                : (isset($value) ? $class::tryFrom($value) : null) ?? $class::cases()[0] ?? null,
            // @codeCoverageIgnoreStart
            default => throw new UnsupportedPropertyTypeException($this->propertyName($property)),
            // @codeCoverageIgnoreEnd
        };

        $allowsNull = $type->allowsNull();

        return function (mixed $value) use (
            $allowsNull,
            $callable,
        ): DataTransferObject|DateTimeImmutable|BackedEnum|null {
            return (null === $value) && $allowsNull ? null : $callable($value);
        };
    }

    private static function builtInMarshaller(ReflectionNamedType $type): Closure
    {
        $allowsNull = $type->allowsNull();
        $name = $type->getName();

        return static function (mixed $value) use ($allowsNull, $name): mixed {
            if ($allowsNull && null === $value) {
                return null;
            }

            settype($value, $name);

            return $value;
        };
    }

    private static function arrayize(mixed $value): mixed
    {
        return match (true) {
            $value instanceof DataTransferObject => (object)$value->toArray(),
            $value instanceof BackedEnum => $value->value,
            $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s e'),
            is_array($value) => array_map(self::arrayize(...), $value),
            is_object($value) => (object)array_map(self::arrayize(...), get_object_vars($value)),
            default => $value,
        };
    }

    /**
     * @throws UnknownClassException
     * @throws UnsupportedClassException
     * @throws UnsupportedPropertyTypeException
     * @throws DiscriminatorException
     */
    private function collectionUnmarshaller(ReflectionProperty $property): Closure
    {
        if (empty($itemProperty = ItemType::itemProperty($property))) {
            if ($prototype = ItemPrototype::fromProperty($property)) {
                $itemProperty = $this->reflectionProperty($prototype->propertyName);
            }
        }

        $unmarshaller = isset($itemProperty)
            ? $this->propertyUnmarshaller($itemProperty, $property)
            : static fn(mixed $value): mixed => $value;

        $allowsNull = ($type = $property->getType())->allowsNull();
        $name = $type->getName();

        return static function (mixed $value) use ($allowsNull, $unmarshaller, $name) {
            if ($allowsNull && null === $value) {
                return null;
            }

            $value = array_map($unmarshaller, is_object($value) ? get_object_vars($value) : ($value ?? []));
            settype($value, $name);

            return 'array' === $name ? array_values($value) : $value;
        };
    }

    /**
     * @throws ReflectionException
     */
    public function hasInitializedProperty(DataTransferObject $object, string $name): bool
    {
        $property = $this->reflectionClass()->getProperty($name);

        return $property->isPublic() && $property->isInitialized($object);
    }
}
