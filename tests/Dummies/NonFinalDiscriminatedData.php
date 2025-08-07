<?php

namespace Webgraphe\PhluxTests\Dummies;

use Webgraphe\Phlux\Attributes\Discriminator;
use Webgraphe\Phlux\Data;

#[Discriminator('type')]
abstract readonly class NonFinalDiscriminatedData extends Data
{
    public string $type;
}
