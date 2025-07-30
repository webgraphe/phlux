<?php

declare(strict_types=1);

namespace Webgraphe\PhluxTests\Dummies;

use Webgraphe\Phlux\Data;
use Webgraphe\PhluxTests\Dummies\Discriminated\AbstractMappedData;
use Webgraphe\PhluxTests\Dummies\Discriminated\AbstractUnmappedData;

readonly class DiscriminatedData extends Data
{
    public AbstractMappedData $mapped;
    public AbstractUnmappedData $unmapped;
}
