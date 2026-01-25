# Yet Another PHP Data Transfer Object Library

A DTO (Data Transfer Object) is a simple object holding data to be moved between different subsystems or layers of an
application. They typically only contain public fields, constructors and getters. Therefore, a DTO:

- MUST be easy to understand
- MUST present and allow data transport in the simplest way
- SHOULD not contain business logic
- SHOULD be immutable, _i.e._ its state should not change after it is created

This is Phlux.

## Declaration

A Phlux DTO is strictly declared using PHP language constructs; there is **no magic**.

The bare minimum to make a class into a DTO is to extend the `readonly` class `Data` which implements
`DataTransferObject`, inheriting `JsonSerializable` and `IteratorAggregate` in the process.

> While no guarantee to be immutable, a `readonly` class ensures initialized properties cannot be tampered with after
initialization, albeit with some caveats:
>
> - A DTO cannot have `static` properties
> - All properties MUST be typed
> - It's still possible to initialize an uninitialized property after construction (albeit in a `protected(set)` manner).

Supported property types range from:

- Scalars (`int`, `float`, `string`, `bool`)
- Composites (`array`, `object`)
- `null`
- Anything extending `Data`
- `DateTimeInterface`, `DateTimeImmutable` and `BackedEnum`

`array` properties are always hydrated as `0`-based lists. `object` properties are always hydrated as a key-value map
instances of `stdClass`. Without attributes, composites can store arbitrary data.

A composite property may narrow the type(s) of items it contains with the `#[ItemType]` attribute, passing a
`class-string` or the name of any aforementioned supported types above.

An `#[ItemPrototype]` attribute may be declared with the the name of another property of the same class to be used as
the prototype of the collection's item (non-`public` properties can used as prototype for that effect).

All properties can be nullable by either prefixing the type name with a question mark or declaring a union with `null`.

When the data for a property is missing from a payload, unless a `#[Present]` attribute
is found on the property (indicating to skip initialization), a default value is assigned:

- `null` when nullable
- `0` for `int`
- `0.0` for `float`
- `false` for `bool`
- `''` for `string`
- `[]` for `array`
- `new stdClass()` for `object`
- Current time for anything implementing `DateTimeInterface` and anything extending `DateTimeImmutable`
- The first `BackedEnum::cases()` item for `enum`
- A new instance with `null` payload for anything implementing `DataTransferObject`

> [!NOTE]
> Uninitialized properties are not serialized.

Adding the `#[Discriminator]` attribute on a `DataTransferObject` class (MUST be `abstract`)
allows for inheritance and polymorphism of DTOs and their properties to hydrate. It must be given the name of a
`final`, non-nullable `string` property on the attributed class containing the discriminator value which can be matched
against a given mapping or composed with the namespace of the discriminated DTO.

There is no support for Union or Intersection properties, except unions with `null`.

Presentation and transportation are handled for all `public` properties (non-`public` properties may be defined but are
ignored for those aspects).

## Instantiation

DTOs may be hydrated in different ways:

The static method `instantiate()` acts as a constructor by accepting parameters named after its public properties.

The static method `from()` is suitable for unmarshalling payloads.

The static method `lazy()` creates lazy objects whose initializations are deferred until their state is observed.

Hydrating methods above accept anything that can be passed to `iterator_to_array()` _i.e._ `iterable` (including `array`,
`ArrayObject` and `DataTransferObject` itself), `stdClass`, or any SPL data structure; freshly decoded JSON can
immediately be passed to any of them to hydrate a DTO, with associative arrays or objects.

```php
<?php

readonly class Person extends Webgraphe\Phlux\Data
{    
    public function __construct(
        public string $firstName,
        #[Present]
        public string $lastName,
    );
}

// in-lieu of the constructor, creating with instantiate() (notice lastName is missing)
$johnDoe = Person::instantiate(firstName: 'John');

var_dump(isset($johnDoe->lastName)); // false, #Present attribute prevented initialization with default value ''

// Creating from an existing DTO
$johnDoeClone = Person::from($johnDoe);

// Encoding to JSON
$json = json_encode($johnDoe);

// Creating from decoded JSON
$johnDoeDeepClone = Person::from(json_decode($johnDoe));

// Creating as lazy-object, hydrated from an associative array
$lazyJohnDoe = Person::lazy(json_decode($json, true));

var_dump(Webgraphe\Phlux\Data::isLazy($lazyJohnDoe)); // true

// Lazy object initializes its properties ONLY when observed 
echo $lazyJohnDoe->firstName; // prints "John"

var_dump(Webgraphe\Phlux\Data::isLazy($lazyJohnDoe)); // false; it's now initialized
```
