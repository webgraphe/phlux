<?php

declare(strict_types=1);

namespace Webgraphe\Phlux\Attributes;

use Attribute;
use DateTimeImmutable;
use DateTimeInterface;
use ReflectionProperty;
use Webgraphe\Phlux\Contracts\DataTransferObject;
use Webgraphe\Phlux\Data;
use Webgraphe\Phlux\Exceptions\UnsupportedClassException;

/**
 * Declares a collection's item type
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class ItemType extends Data
{
    private mixed $mixed;
    private string $string;
    private int $int;
    private int $integer;
    private float $float;
    private float $double;
    private bool $bool;
    private bool $boolean;
    private null $null;
    private array $array;
    private object $object;
    private DataTransferObject $DataTransferObject;
    private DateTimeInterface $DateTimeInterface;
    private DateTimeImmutable $DateTimeImmutable;

    private const array CLASS_PROPERTIES = [
        DataTransferObject::class => 'DataTransferObject',
        DateTimeInterface::class => 'DateTimeInterface',
        DateTimeImmutable::class => 'DateTimeImmutable',
    ];

    public function __construct(public string $type) {}

    /**
     * @throws UnsupportedClassException
     */
    public static function itemProperty(ReflectionProperty $collectionProperty): ?ReflectionProperty
    {
        if (empty($attribute = ($collectionProperty->getAttributes(self::class)[0] ?? null)?->newInstance())) {
            return null;
        }

        /** @var self $attribute */
        if (($classReflection = self::meta()->reflectionClass())->hasProperty($attribute->type)) {
            $selfReflection = (static fn() => $classReflection->getProperty($attribute->type))();
        } else {
            foreach (self::CLASS_PROPERTIES as $class => $propertyName) {
                if (is_a($attribute->type, $class, true)) {
                    $selfReflection = (static fn () => $classReflection->getProperty($propertyName))();
                    break;
                }
            }

        }

        if (!isset($selfReflection)) {
            throw new UnsupportedClassException($attribute->type);
        }

        return $selfReflection;
    }
}
