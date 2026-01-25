<?php

declare(strict_types=1);

namespace Webgraphe\PhluxTests\Dummies;

use Webgraphe\Phlux\Attributes\Present;
use Webgraphe\Phlux\Data;

readonly class IdentityData extends Data
{
    public function __construct(
        #[Present] public string $name,
    ) {}
}
