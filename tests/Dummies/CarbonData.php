<?php

namespace Webgraphe\PhluxTests\Dummies;

use Carbon\CarbonImmutable;
use Webgraphe\Phlux\Data;

final readonly class CarbonData extends Data
{
    public CarbonImmutable $datetime;
}
