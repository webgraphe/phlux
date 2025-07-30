<?php

declare(strict_types=1);

namespace Webgraphe\Phlux\Attributes;

use Attribute;
use ReflectionNamedType;

/**
 * Declares the item type of an array or object
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class ItemType
{
    public const array BUILTIN = [
        'mixed' => true,
        'string' => true,
        'bool' => true,
        'boolean' => true,
        'int' => true,
        'integer' => true,
        'float' => true,
        'double' => true,
        'null' => true,
        'array' => true,
        'object' => true,
    ];

    // @phpstan-ignore property.uninitializedReadonly
    private ReflectionNamedType $reflectionNamedType;

    public function __construct(public string $type) {}

    public function isBuiltin(): bool
    {
        return self::BUILTIN[$this->type] ?? false;
    }

    public function asReflectionNamedType(): ReflectionNamedType
    {
        return $this->reflectionNamedType ??= new class($this) extends ReflectionNamedType {
            public function __construct(private readonly ItemType $itemType) {}

            public function getName(): string
            {
                return $this->itemType->type;
            }

            public function isBuiltin(): bool
            {
                return $this->itemType->isBuiltin();
            }

            public function allowsNull(): bool
            {
                return false;
            }
        };
    }
}
