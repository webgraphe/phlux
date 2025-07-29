<?php

declare(strict_types=1);

namespace Webgraphe\Phlux\Contracts;

use Closure;

interface EventEmitter
{
    public static function on(string $event, Closure|EventListener $listener): void;

    public static function off(string $signal, Closure|EventListener $listener): void;
}
