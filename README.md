# Yet Another PHP Data Transfer Object Library?

Not so fast!

A true Data Transfer Object (DTO) in PHP is a class focused solely on storing and structuring data for transfer
between different parts of an application with key features such as:

- strong typing
- immutability
- absence of business logic

As it is, the DTO below checks the aforementioned requirements.

```php
readonly class UserProfile
{
    public function __construct(
        public int $id,
        public string $firstName,
        public string $lastName,
        public string $email,
    ) {}
}
```

However, things quickly get complicated when:

- a constructor with promoted properties becomes crowded
- transformation is required but the state of the original object must remain intact
- serialization/deserialization can be tedious
- correctness of complex structures such as collections of discriminable DTOs are challenging

This ^^ is Phlux, a PHP library with no other dependencies than PHP itself.

## Declaration

A Phlux DTO is strictly declared using PHP language constructs; there is _**no magic**_.

To make a Phlux DTO: extend the `readonly` class `Data`.

A Phlux DTO can declare `public` properties, a constructor promoting `public` properties, or both!

### Supported property types range from:

- `int`, `float`, `string` and `bool`
- `DateTimeInterface`, or anything extending `DateTimeImmutable`
- Backed-enumerations
- Anything extending `Data`
- Composites (`array`, `object`) of all the above

Any property can be nullable, declared with the `?` prefix or as a union with `null`.

### Composites

An `array` properties is always hydrated as `0`-based list.

An `object` property is always hydrated as a key-value map instance of `stdClass`.

Without attributes, composites can store arbitrary data.

A composite property may narrow the type(s) of items it contains with the `#[ItemType]` attribute, passing a
`class-string` or the name of any aforementioned supported types above.

An `#[ItemPrototype]` attribute may be declared with the name of another property of the same class to be used as
the prototype of the collection's item (non-`public` properties can used as prototype for that effect).

### Polymorphism

Adding the `#[Discriminator]` attribute on a `DataTransferObject` class (MUST be `abstract`)
allows for inheritance and polymorphism of DTOs and their properties to hydrate. It must be given the name of a
`final`, non-nullable `string` property on the attributed class containing the discriminator value which can be matched
against a given mapping or composed with the namespace of the discriminated DTO.

### Limitations

There is no support (yet) for Union or Intersection properties, except unions with `null`.

Presentation and transportation are handled for all `public` properties (non-`public` properties may be defined but are
not serialized).

Uninitialized properties are not serialized.

## Instantiation

DTOs may be hydrated in different ways:

The static method `instantiate()` acts as a constructor by accepting parameters named after its public properties. For
discriminated DTOs, it resolves the discriminator value automatically.

The static method `from()` unmarshalls payloads, such as decoded JSON, `stdClass`, `ArrayObject` or SPL data structures
(that can be transformed into raw PHP composites). It discriminates which class to instantiate from the payload.

Methods `lazyInstantiate()` and `lazyFrom()` creates lazy instances that initializes only when observed, which may
reduce the number of precious CPU cycles when dealing with big nested DTOs and complex business logic partially
navigating them.

> [!CAUTION]
> Lazy DTO instantiations will defer exceptions that would otherwise have been thrown at creation time with
> their non-lazy corresponding methods only when they are observed for the first time; it is advised to unit test
> your work without lazy instances to validate your Data Transfer Object definitions.

### Default values

When the data for a property is missing from a payload, unless a `#[Present]` attribute
is found on the property (indicating to skip initialization), a default value is assigned:

- `0` for `int`
- `0.0` for `float`
- `false` for `bool`
- `''` for `string`
- `[]` for `array`
- `new stdClass()` for `object`
- Current time for anything implementing `DateTimeInterface` and anything extending `DateTimeImmutable`
- The first `BackedEnum::cases()` item for `enum`
- A new instance with `null` payload for anything implementing `DataTransferObject`
- `null` when nullable

## Examples

Polymorphism:

```php
enum Color: string
{
    case RED = 'red';
    case GREEN = 'green';
    case BLUE = 'blue';
}

#[Webgraphe\Phlux\Attributes\Discriminator('type', self::MAPPING)]
abstract readonly class Shape extends Webgraphe\Phlux\Data
{
    public const MAPPING = [
        'square' => Square::class,
        'circle' => Circle::class
    ];

    public string $type;
    public Color $color;
}

final readonly class Square extends Shape
{
    public float $length;
}

final readonly class Circle extends Shape
{
    public float $radius;
}

$square = Square::instantiate(length: 4);
$square->length; // 4
// uninitialized enum defaults to first case
$square->color; // Color::RED
$square->type; // 'square'
json_encode($square); // '{"type":"square","color":"red","length":4}'

$circle = Shape::from(['type' => 'circle', 'color':'green', 'radius' => 5.0]);
// class inferred from discriminator
$circle::class; // 'Circle'
$circle->color; // Color::GREEN
$circle->radius; // 5.0
```

Constructor with parameters promoted as properties, and collection types:

```php
final readonly class UserGroup extends Webgraphe\Phlux\Data
{
    public int $id;
    public int $name;
    #[Webgraphe\Phlux\Attributes\ItemPrototype('members')]
    public array $levelMembers;
    
    #[Webgraphe\Phlux\Attributes\ItemType(UserProfile::class)]
    private array $members;
}

final readonly class UserProfile extends Webgraphe\Phlux\Data
{
    public string $firstName;
    public string $lastName;
    #[Webgraphe\Phlux\Attributes\Present]
    public string $email;

    public function __construct(
        public int $id,
    )
}

$user = new UserProfile(123);
$user->id; // 123
isset($user->firstName); // false
isset($user->lastName); // false
isset($user->email); // false

$user = UserProfile::lazyInstantiate(id: 123, firstName: 'John');
// Object is lazy
Data::isLazy($user); // true
// lastName will always be initialized
isset($user->lastName); // true
// email is only initialized if a value is given (present)
isset($user->email); // false
// Object is no longer lazy now that we accessed the lastName and email properties
Data::isLazy($user); // false
// Serialization omits uninitialized properties
json_encode($user); // '{"id":123,"firstName":"John",'lastName":""}

$userGroup = UserGroup::instantiate(members:[0 => [$user]]);
count($userGroup->levelMembers); // 1
count($userGroup->levelMembers[$level = 0]); // 1
$userGroup->levelMembers[$level][0] === $user; // true
```
