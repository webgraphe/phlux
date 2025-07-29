<?php

declare(strict_types=1);

namespace Webgraphe\PhluxTests\Dummies;

use Webgraphe\Phlux\Attributes\Present;
use Webgraphe\Phlux\Data;

readonly class IdentityData extends Data
{
    #[Present]
    public string $name;
}
