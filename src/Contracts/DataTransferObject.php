<?php

declare(strict_types=1);

namespace Webgraphe\Phlux\Contracts;

use ArrayAccess;
use IteratorAggregate;
use JsonSerializable;
use stdClass;

interface DataTransferObject extends ArrayAccess, JsonSerializable, IteratorAggregate
{
    public static function instantiate(mixed ...$arguments): static;

    public static function lazy(iterable|stdClass|null $data): static;

    public static function from(iterable|stdClass|null $data): static;

    public function toArray(): array;
}
