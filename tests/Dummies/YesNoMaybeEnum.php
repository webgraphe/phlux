<?php

declare(strict_types=1);

namespace Webgraphe\PhluxTests\Dummies;

enum YesNoMaybeEnum: string
{
    case YES = 'yes';
    case NO = 'no';
    case MAYBE = 'maybe';
}
