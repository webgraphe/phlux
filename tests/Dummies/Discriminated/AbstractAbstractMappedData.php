<?php

declare(strict_types=1);

namespace Webgraphe\PhluxTests\Dummies\Discriminated;

use Webgraphe\Phlux\Attributes\Discriminator;
use Webgraphe\Phlux\Data;

#[Discriminator('type', self::MAP)]
abstract readonly class AbstractAbstractMappedData extends AbstractMappedData
{
}
