<?php

declare(strict_types=1);

namespace Webgraphe\PhluxTests\Dummies;

use DateTime;
use Webgraphe\Phlux\Attributes\ItemType;
use Webgraphe\Phlux\Data;

readonly class UnsupportedClassCollectionData extends Data
{
    // DateTime is not immutable
    #[ItemType(DateTime::class)]
    public array $dateTimes;
}
