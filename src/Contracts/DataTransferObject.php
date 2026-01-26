<?php

declare(strict_types=1);

namespace Webgraphe\Phlux\Contracts;

use ArrayAccess;
use IteratorAggregate;
use JsonSerializable;
use stdClass;

interface DataTransferObject extends ArrayAccess, JsonSerializable, IteratorAggregate
{
    /**
     * Creates a non-discriminated instance hydrating properties from named arguments.
     */
    public static function instantiate(mixed ...$arguments): static;

    /**
     * Creates a lazy non-discriminated instance hydrating properties from named arguments.
     */
    public static function lazyInstantiate(mixed ...$arguments): static;

    /**
     * Creates a discriminated instance hydrating properties from an iterable or object.
     */
    public static function from(iterable|stdClass|null $data): static;

    /**
     * Creates a lazy discriminated instance hydrating properties from an iterable or object.
     */
    public static function lazyFrom(iterable|stdClass|null $data): static;

    public function toArray(): array;
}
