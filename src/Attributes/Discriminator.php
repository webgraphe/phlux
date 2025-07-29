<?php

namespace Webgraphe\Phlux\Attributes;

use Attribute;
use stdClass;
use Webgraphe\Phlux\Data;
use Webgraphe\Phlux\Exceptions\DiscriminatorException;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Discriminator
{
    /**
     * @param string $propertyName
     * @param array<string, class-string<Data>>|null $mapping
     */
    public function __construct(public string $propertyName, public ?array $mapping = null) {}

    /**
     * @return class-string<Data>
     * @throws DiscriminatorException
     */
    public function resolveClass(iterable|stdClass|null $data, string $parent): string
    {
        $array = $data instanceof stdClass ? get_object_vars($data) : iterator_to_array($data ?? []);
        $stub = "$parent::\$$this->propertyName";
        if (!isset($array[$this->propertyName])) {
            throw new DiscriminatorException("$stub's data is missing");
        }

        $discriminator = $array[$this->propertyName];

        if ($this->mapping) {
            if (($class = $this->mapping[$discriminator] ?? null)) {
                if (class_exists($class)) {
                    return $this->mapping[$discriminator];
                }

                throw new DiscriminatorException("$stub's '$discriminator' maps to undefined class $class");
            }

            throw new DiscriminatorException("$stub's '$discriminator' is not mapped");
        }

        $class = str_replace('/', '\\', dirname(str_replace('\\', '/', $parent)) . '/' . $discriminator);
        if (class_exists($class)) {
            return $class;
        }

        throw new DiscriminatorException("$stub's '$discriminator' resolved to undefined class $class");
    }
}
