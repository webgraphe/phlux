<?php

namespace Webgraphe\PhluxTests\Dummies\Discriminated;

use Webgraphe\Phlux\Attributes\Discriminator;
use Webgraphe\Phlux\Data;

#[Discriminator('type')]
readonly class AbstractUnmappedData extends Data
{
    public const string UnmappedLeftData = 'UnmappedLeftData';
    public const string UnmappedRightData = 'UnmappedRightData';

    public string $type;
}
