<?php

namespace Webgraphe\PhluxTests\Dummies\Discriminated;

use Webgraphe\Phlux\Attributes\Discriminator;
use Webgraphe\Phlux\Data;

#[Discriminator('type')]
readonly class MappedLeftData extends AbstractMappedData
{
}
