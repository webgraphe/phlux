<?php

declare(strict_types=1);

namespace Webgraphe\PhluxTests\Dummies;

use DateTimeImmutable;
use DateTimeInterface;
use Webgraphe\Phlux\Attributes\ItemPrototype;
use Webgraphe\Phlux\Attributes\ItemType;
use Webgraphe\Phlux\Attributes\Present;

readonly class TestData extends IdentityData
{
    public string|null $nullableString;
    public bool $bool;
    public int $int;
    public float $float;
    public array $array;
    public ?object $object;
    #[ItemType('string'), Present]
    public array $strings;
    #[ItemPrototype('nullableString'), Present]
    public object $nullableStringMap;
    #[ItemPrototype('strings'), Present]
    public array $stringsArray;
    #[ItemType('int'), Present]
    public array $ints;
    #[ItemType('float'), Present]
    public array $floats;
    #[ItemType('bool'), Present]
    public array $bools;
    #[Present]
    public ?self $data;
    #[Present]
    public DateTimeInterface $dateTimeInterface;
    #[Present]
    public DateTimeImmutable $dateTimeImmutable;
    #[Present]
    public YesNoMaybeEnum $yesNoMaybeEnum;
    #[Present]
    public OneTwoThreeEnum $oneTwoThreeEnum;
    #[ItemType(DateTimeInterface::class), Present]
    public array $dateTimeInterfaces;
    #[ItemType(DateTimeImmutable::class), Present]
    public array $dateTimeImmutables;
}
