<?php

declare(strict_types=1);

namespace Webgraphe\Phlux\Contracts;

interface Initializer
{
    public function __invoke(): void;
}
