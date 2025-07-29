<?php

declare(strict_types=1);

namespace Webgraphe\Phlux\Attributes;

use Attribute;

/**
 * Declares the item type of an array or object by using another property as prototype
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class ItemPrototype
{
    public function __construct(public string $propertyName) {}
}
