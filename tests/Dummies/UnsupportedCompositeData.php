<?php

declare(strict_types=1);

namespace Webgraphe\PhluxTests\Dummies;

use DateTime;
use Webgraphe\Phlux\Attributes\ItemType;
use Webgraphe\Phlux\Data;

readonly class UnsupportedCompositeData extends Data
{
    #[ItemType(DateTime::class)]
    public array $dateTimes;
}
