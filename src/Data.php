<?php

declare(strict_types=1);

namespace Webgraphe\Phlux;

use stdClass;
use Traversable;
use Webgraphe\Phlux\Contracts\DataTransferObject;
use Webgraphe\Phlux\Contracts\Initializer;
use Webgraphe\Phlux\Exceptions\DiscriminatorException;
use Webgraphe\Phlux\Exceptions\PresentException;

/**
 * Add support for discriminable
 * Make Data objects into resources
 * Lazy properties?
 */
abstract readonly class Data implements DataTransferObject
{
    final public function __construct(iterable|stdClass|null $data = null)
    {
        $array = $data instanceof stdClass ? get_object_vars($data) : iterator_to_array($data ?? []);
        foreach (static::meta()->unmarshallers() as $name => $unmarshaller) {
            try {
                $this->$name = array_key_exists($name, $array)
                    ? $unmarshaller($array[$name])
                    : $unmarshaller();
            } catch (PresentException) {
            }
        }
    }

    /**
     * @throws DiscriminatorException
     */
    final public static function lazy(iterable|stdClass|null $data): static
    {
        /**
         * @noinspection PhpIncompatibleReturnTypeInspection
         * @phpstan-ignore return.type
         */
        return self::discriminatedMeta(static::meta(), $data)->reflectionClass()->newLazyGhost(
            function (self $instance) use ($data) {
                Meta::lazy(static fn() => $instance->__construct($data));
            },
        );
    }

    final public static function isLazy(self $instance): bool
    {
        return Meta::get($instance::class)->reflectionClass()->isUninitializedLazyObject($instance);
    }

    /**
     * @throws DiscriminatorException
     */
    final public static function from(iterable|stdClass|null $data): static
    {
        // @phpstan-ignore return.type
        return new (self::discriminatedMeta(static::meta(), $data)->class)($data);
    }

    /**
     * @throws DiscriminatorException
     */
    private static function discriminatedMeta(Meta $meta, iterable|stdClass|null $data): Meta
    {
        return ($discriminator = $meta->getDiscriminator())
            ? Meta::get($discriminator->resolveClass($data, $meta->class))
            : $meta;
    }

    final public static function meta(): Meta
    {
        return Meta::get(static::class);
    }

    final public function jsonSerialize(): object
    {
        return (object)iterator_to_array(static::meta()->marshal($this));
    }

    final public function toArray(): array
    {
        return json_decode(json_encode($this), true);
    }

    final public function getIterator(): Traversable
    {
        // Using Meta to return public vars only
        yield from static::meta()->vars($this);
    }

    /**
     * @param Initializer|callable(): void $initializer
     * @return static
     */
    final public static function instantiate(Initializer|callable $initializer): static
    {
        $reflection = static::meta()->reflectionClass();
        /** @var static $instance */
        $instance = (static fn() => $reflection->newInstanceWithoutConstructor())();
        $initializer(...)->call($instance);
        $properties = static::meta()->reflectionProperties();
        foreach (static::meta()->unmarshallers() as $name => $unmarshaller) {
            if (!$properties[$name]->isInitialized($instance)) {
                try {
                    $instance->$name = $unmarshaller();
                } catch (PresentException) {
                }
            }
        }

        return $instance;
    }

    final public function offsetExists(mixed $offset): bool
    {
        return static::meta()->hasInitializedProperty($this, (string)$offset);
    }

    final public function offsetGet(mixed $offset): mixed
    {
        return $this->$offset;
    }

    final public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->$offset = $value;
    }

    final public function offsetUnset(mixed $offset): void
    {
        unset($this->$offset);
    }
}
