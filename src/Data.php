<?php

declare(strict_types=1);

namespace Webgraphe\Phlux;

use IteratorAggregate;
use JsonSerializable;
use stdClass;
use Traversable;
use Webgraphe\Phlux\Exceptions\PresentException;

/**
 * Add support for discriminable
 * Make Data objects into resources
 * Lazy properties?
 */
abstract readonly class Data implements JsonSerializable, IteratorAggregate
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

    final public static function lazy(iterable|stdClass|null $data): static
    {
        // TODO Discriminate
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return static::meta()->reflectionClass()->newLazyGhost(
            function (self $instance) use ($data) {
                Meta::lazy(static fn() => $instance->__construct($data));
            },
        );
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
        yield from self::meta()->vars($this);
    }
}
