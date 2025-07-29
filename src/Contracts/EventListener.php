<?php

declare(strict_types=1);

namespace Webgraphe\Phlux\Contracts;

interface EventListener
{
    public function __invoke(EventEmitter $emitter, string $message, mixed ...$args): void;
}
