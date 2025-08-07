<?php

namespace Webgraphe\PhluxTests\Dummies;

use Webgraphe\Phlux\Attributes\Discriminator;
use Webgraphe\Phlux\Data;

#[Discriminator('type')]
readonly class NonAbstractDiscriminatedData extends Data
{
    final public string $type;
}
