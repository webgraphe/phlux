<?php

namespace Webgraphe\Phlux\Contracts;

use JsonSerializable;
use stdClass;

interface DataTransferObject extends JsonSerializable
{
    public function __construct(iterable|stdClass|null $data = null);

    public static function lazy(iterable|stdClass|null $data): static;

    public static function from(iterable|stdClass|null $data): static;

    public function toArray(): array;
}
