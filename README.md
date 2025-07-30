# Yet Another PHP Data Transfer Object Library

A DTO (Data Transfer Object) is a simple object holding data.

- It MUST be easy to understand
- It MUST present and allow data transport in the simplest way
- It MUST not contain business logic
- It SHOULD be immutable, _i.e._ immune to modifications

This is Phlux.

## Declaration

A Phlux DTO is strictly declared using PHP language constructs.

One can extend the `readonly` class `Data` or implement `DataTransferObject`
which in on itself is `JsonSerializable` and `IteratorAggregate`.

Presentation and transportation are handled for all `public` properties (non-`public` properties may be defined but are
ignored for those aspects).

While no guarantee to be immutable, a `readonly` class ensures initialized properties cannot be tampered with after
initialization, albeit with some caveats:

- A DTO cannot have `static` properties
- All properties MUST be typed
- It's still possible to initialized an uninitialized property after construction (albeit in a `protected(set)` manner). 

Property types range from built-in (`int`, `float`, `string`, `bool`, `null`).

An `array` is always hydrated as a `0`-based list and `object` as a key-value map. Without attributes, they can store
arbitrary data. Specify the item type with the `#[ItemType]` attribute passing a `class-string` of anything implementing
`DataTransferObject`, `DateTimeInterface`, anything extending `DateTimeImmutable` or a "backed" `enum`. Specify an item
prototype with the `#[ItemPrototype]` attribute passing the name of a property whose type will be used as the prototype
of the collection's item (non-`public` properties can used as prototype for that matter).

All properties can be nullable. When the data for a property is missing from a payload, unless a `#[Present]` attribute
is found on the property indicating to skip initialization, a default value is assigned

- `null` when nullable
- `0` for `int`
- `0.0` for `float`
- `false` for `bool`
- `''` for `string`
- `[]` for `array`
- `new stdClass` for `object`
- Current time for anything implementing `DateTimeInterface`
- The first `BackedEnum::cases()` item for `enum`
- A new instance with `null` payload for anything implementing `DataTransferObject`

Adding the `#[Discriminator]` attribute on a `DataTransferObject` class (usually `abstract`)
allows for inheritance and polymorphism of DTOs and their properties to hydrate. It must be given the name of a
non-nullable `string` property on the attributed class containing the discriminator value which can be matched against
a given mapping or composed with the namespace of the discriminated DTO.

There is no support for Union or Intersection properties.

## Instantiation

DTOs may be hydrated in different ways:

- Using the exception safe constructor `__construct()`
- Using the static `from()` method
- Using the static `lazy()` method, which creates a lazy object whose initialization is deferred until its state is
  observed or modified

All the methods above accept anything that can be passed to `iterator_to_array()`, _i.e._ `iterable` (including `array`,
`ArrayObject` and `DataTransferObject` itself), `stdClass`, or any SPL data structure; freshly decoded JSON can
immediately be passed to any of them to hydrate a DTO.

```php
<?php

readonly class Person extends Webgraphe\Phlux\Data
{
    public string $firstName;
    public string $lastName;
}

$json = '{"firstName":"John","lastName":"Doe"}';
// Passing JSON-decoded data (a stdClass instance)
$person = new Person(json_decode($json));
// Passing another DTO
$person = Person::from($person);
// A lazy-object hydrated from an associative array
$lazyPerson = Person::lazy(json_decode($json, true));
// Object initializes its properties ONLY when reading them 
echo $lazyPerson->firstName;
```
