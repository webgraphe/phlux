<?php

declare(strict_types=1);

namespace Webgraphe\Phlux;

use IteratorAggregate;
use stdClass;
use Traversable;
use Webgraphe\Phlux\Contracts\DataTransferObject;
use Webgraphe\Phlux\Exceptions\DiscriminatorException;
use Webgraphe\Phlux\Exceptions\PresentException;

/**
 * Add support for discriminable
 * Make Data objects into resources
 * Lazy properties?
 */
abstract readonly class Data implements DataTransferObject, IteratorAggregate
{
    final public function __construct(iterable|stdClass|null $data = null)
    {
        $array = $data instanceof stdClass ? get_object_vars($data) : iterator_to_array($data ?? []);
        foreach (static::meta()->unmarshallers() ?? [] as $name => $unmarshaller) {
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
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return static::discriminatedMeta($data)->reflectionClass()->newLazyGhost(
            function (self $instance) use ($data) {
                Meta::lazy(static fn() => $instance->__construct($data));
            },
        );
    }

    /**
     * @throws DiscriminatorException
     */
    final public static function from(iterable|stdClass|null $data): static
    {
        return new (static::discriminatedMeta($data)->class)($data);
    }

    /**
     * @throws DiscriminatorException
     */
    private static function discriminatedMeta(iterable|stdClass|null $data): Meta
    {
        return ($discriminator = static::meta()->getDiscriminator())
            ? Meta::get($discriminator->resolveClass($data, static::class))
            : static::meta();
    }

    public static function meta(): Meta
    {
        return Meta::get(static::class);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return iterator_to_array(static::meta()->marshal($this));
    }

    public function getIterator(): Traversable
    {
        // Using Meta to return public vars only
        yield from self::meta()->vars($this);
    }
}
