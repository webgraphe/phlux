<?php

declare(strict_types=1);

namespace Webgraphe\PhluxTests\Dummies\Discriminated;

use Webgraphe\Phlux\Attributes\Discriminator;
use Webgraphe\Phlux\Data;

#[Discriminator('type', self::MAP)]
abstract readonly class AbstractMappedData extends Data
{
    public const string left = 'left';
    public const string right = 'right';
    public const string undefined = 'undefined';

    /** @noinspection PhpUndefinedClassInspection */
    public const array MAP = [
        self::left => MappedLeftData::class,
        self::right => MappedRightData::class,
        self::undefined => Undefined::class,
    ];

    public string $type;
}
