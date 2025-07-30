<?php

declare(strict_types=1);

namespace Webgraphe\PhluxTests\Dummies;

use Webgraphe\Phlux\Data;

readonly class UnionData extends Data
{
    public int|float $number;
}
