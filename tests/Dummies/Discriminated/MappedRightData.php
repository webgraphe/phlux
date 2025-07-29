<?php

namespace Webgraphe\PhluxTests\Dummies\Discriminated;

use Webgraphe\Phlux\Attributes\Discriminator;

#[Discriminator('type')]
readonly class MappedRightData extends AbstractMappedData
{
}
