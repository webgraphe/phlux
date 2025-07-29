<?php

declare(strict_types=1);

namespace Webgraphe\Phlux\Concerns;

use Closure;
use Webgraphe\Phlux\Contracts\EventEmitter;
use Webgraphe\Phlux\Contracts\EventListener;

/**
 * @mixin EventEmitter
 */
trait EmitsEvents
{
    /** @var array<string, array<string, Closure|EventListener>> */
    private static array $listeners = [];

    public static function on(string $event, Closure|EventListener $listener): void
    {
        self::$listeners[$event][spl_object_hash($listener)] = $listener;
    }

    public static function off(string $signal, Closure|EventListener $listener): void
    {
        unset(self::$listeners[$signal][spl_object_hash($listener)]);
    }

    protected static function emit(EventEmitter $emitter, string $signal, mixed ...$args): void
    {
        foreach (self::$listeners[$signal] ?? [] as $listener) {
            $listener($emitter, $signal, ...$args);
        }
    }
}
