<?php

declare(strict_types=1);

namespace Webgraphe\Phlux;

use ReflectionException;
use stdClass;
use Traversable;
use Webgraphe\Phlux\Contracts\DataTransferObject;
use Webgraphe\Phlux\Exceptions\DiscriminatorException;
use Webgraphe\Phlux\Exceptions\PresentException;

abstract readonly class Data implements DataTransferObject
{
    final public static function instantiate(mixed ...$arguments): static
    {
        $reflection = static::meta()->reflectionClass();
        /**
         * @var static $instance
         */
        $instance = $reflection->newInstanceWithoutConstructor(...)();

        return $instance->hydrate($arguments);
    }

    final public static function lazyInstantiate(mixed ...$arguments): static
    {
        /**
         * @noinspection PhpIncompatibleReturnTypeInspection
         * @phpstan-ignore return.type
         */
        return static::meta()->reflectionClass()->newLazyGhost(
            function (self $instance) use ($arguments): void {
                Meta::lazy($instance->hydrate(...), $arguments);
            },
        );
    }

    /**
     * @throws DiscriminatorException
     */
    final public static function from(iterable|stdClass|null $data): static
    {
        $array = $data instanceof stdClass ? get_object_vars($data) : iterator_to_array($data ?? []);

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        // @phpstan-ignore return.type
        return (self::discriminatedMeta(static::meta(), $data)->class)::instantiate(...$array);
    }

    /**
     * @throws DiscriminatorException
     */
    final public static function lazyFrom(iterable|stdClass|null $data): static
    {
        /**
         * @noinspection PhpIncompatibleReturnTypeInspection
         * @phpstan-ignore return.type
         */
        return self::discriminatedMeta(static::meta(), $data)->reflectionClass()->newLazyGhost(
            function (self $instance) use ($data): void {
                Meta::lazy($instance->hydrate(...), $data);
            },
        );
    }

    final public static function isLazy(self $instance): bool
    {
        return Meta::get($instance::class)->reflectionClass()->isUninitializedLazyObject($instance);
    }

    private function hydrate(iterable|stdClass|null $data = null): static
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

        return $this;
    }

    public function with(mixed ...$arguments): static
    {
        return static::instantiate(...($arguments + iterator_to_array(self::meta()->vars($this))));
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

    final public function offsetExists(mixed $offset): bool
    {
        try {
            return static::meta()->hasInitializedProperty($this, (string)$offset);
        } catch (ReflectionException) {
            return false;
        }
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
