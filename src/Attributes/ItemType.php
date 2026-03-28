<?php

declare(strict_types=1);

namespace Webgraphe\Phlux\Attributes;

use Attribute;
use BackedEnum;
use DateTimeInterface;
use ReflectionNamedType;
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
    private BackedEnum $BackedEnum;

    private const array CLASS_PROPERTIES = [
        DataTransferObject::class => 'DataTransferObject',
        DateTimeInterface::class => 'DateTimeInterface',
        BackedEnum::class => 'BackedEnum',
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
            return (static fn() => $classReflection->getProperty($attribute->type))();
        }

        foreach (self::CLASS_PROPERTIES as $class => $propertyName) {
            if (!is_a($attribute->type, $class, true)) {
                continue;
            }

            return new class(ItemType::class, $propertyName, $attribute->type) extends ReflectionProperty {
                private ReflectionNamedType $type;

                public function __construct(string $class, string $propertyName, string $typeClass)
                {
                    parent::__construct($class, $propertyName);
                    $this->type = new class($typeClass) extends ReflectionNamedType {
                        public function __construct(private readonly string $typeClass) {}

                        public function getName(): string
                        {
                            return $this->typeClass;
                        }

                        public function isBuiltin(): bool
                        {
                            return false;
                        }

                        public function allowsNull(): bool
                        {
                            return false;
                        }
                    };
                }

                public function getType(): ReflectionNamedType
                {
                    return $this->type;
                }
            };
        }

        // @codeCoverageIgnoreStart
        throw new UnsupportedClassException($attribute->type);
        // @codeCoverageIgnoreEnd
    }
}
