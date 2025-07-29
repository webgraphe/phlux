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
    public ?string $nullableString;
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
}
