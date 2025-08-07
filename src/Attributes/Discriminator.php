<?php

declare(strict_types=1);

namespace Webgraphe\Phlux\Attributes;

use Attribute;
use stdClass;
use Webgraphe\Phlux\Contracts\DataTransferObject;
use Webgraphe\Phlux\Data;
use Webgraphe\Phlux\Exceptions\DiscriminatorException;
use Webgraphe\Phlux\Exceptions\InvalidNamespaceDiscriminatorException;
use Webgraphe\Phlux\Exceptions\InvalidValueDiscriminatorException;
use Webgraphe\Phlux\Exceptions\MissingDataDiscriminatorException;
use Webgraphe\Phlux\Exceptions\UndefinedClassDiscriminatorException;
use Webgraphe\Phlux\Exceptions\UnmappedClassDiscriminatorException;
use Webgraphe\Phlux\Exceptions\UnmappedValueDiscriminatorException;

/**
 * MUST be declared on abstract class
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Discriminator
{
    /**
     * @param string $propertyName
     * @param array<string, class-string<Data>>|null $mapping
     */
    public function __construct(public string $propertyName, public ?array $mapping = null) {}

    private static function namespace(string $class): string
    {
        return ($pos = strrpos($class, '\\')) ? substr($class, 0, $pos) : '';
    }

    /**
     * @return class-string<DataTransferObject>
     * @throws DiscriminatorException
     */
    public function resolveClass(iterable|stdClass|null $data, string $parent): string
    {
        $array = $data instanceof stdClass ? get_object_vars($data) : iterator_to_array($data ?? []);
        $stub = "$parent::\$$this->propertyName";
        if (!isset($array[$this->propertyName])) {
            throw new MissingDataDiscriminatorException($stub);
        }

        is_string($discriminator = $array[$this->propertyName])
        or throw new InvalidValueDiscriminatorException($stub);

        if ($this->mapping) {
            if (($class = $this->mapping[$discriminator] ?? null)) {
                if (class_exists($class)) {
                    return $this->mapping[$discriminator];
                }

                throw new UndefinedClassDiscriminatorException($class);
            }

            throw new UnmappedValueDiscriminatorException("$stub=$discriminator");
        }

        $class = ltrim(self::namespace($parent) . '\\' . $discriminator, '\\');
        if (class_exists($class) and is_subclass_of($class, DataTransferObject::class)) {
            return $class;
        }

        throw new UndefinedClassDiscriminatorException($class);
    }

    /**
     * @throws DiscriminatorException
     */
    public function resolveValue(string $class, string $declaringClass): ?string
    {
        if ($this->mapping) {
            if (false !== ($value = array_search($class, $this->mapping, true))) {
                return $value;
            }

            throw new UnmappedClassDiscriminatorException($class);
        }

        $namespace = self::namespace($declaringClass);
        if (str_starts_with($class, $namespace)) {
            return $namespace ? substr($class, strlen($namespace) + 1) : $class;
        }

        throw new InvalidNamespaceDiscriminatorException($class);
    }
}
