<?php

declare(strict_types=1);

namespace Webgraphe\Phlux\Contracts;

use IteratorAggregate;
use JsonSerializable;
use stdClass;

interface DataTransferObject extends JsonSerializable, IteratorAggregate
{
    public function __construct(iterable|stdClass|null $data = null);

    public static function lazy(iterable|stdClass|null $data): static;

    public static function from(iterable|stdClass|null $data): static;

    public function toArray(): array;
}
