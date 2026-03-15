<?php

declare(strict_types=1);

namespace Webgraphe\Phlux\Attributes;

use Attribute;
use ReflectionProperty;

/**
 * Declares the item type of array or object by using another property as prototype
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class ItemPrototype
{
    public function __construct(public string $propertyName) {}

    public static function fromProperty(ReflectionProperty $property): ?self
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ($property->getAttributes(self::class)[0] ?? null)?->newInstance();
    }
}
