<?php

namespace Webgraphe\PhluxTests\Dummies;

use Webgraphe\Phlux\Attributes\Discriminator;
use Webgraphe\Phlux\Data;

#[Discriminator('type')]
abstract readonly class NonStringDiscriminatedData extends Data
{
    final public ?string $type;
}
