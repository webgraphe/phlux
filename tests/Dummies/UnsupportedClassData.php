<?php

namespace Webgraphe\PhluxTests\Dummies;

use DateTime;
use Webgraphe\Phlux\Data;

readonly class UnsupportedClassData extends Data
{
    // DateTime is not immutable
    public DateTime $dateTime;
}
