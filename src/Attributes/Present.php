<?php

declare(strict_types=1);

namespace Webgraphe\Phlux\Attributes;

use Attribute;

/**
 * Indicates a property will be assigned a value only if provided from the payload
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Present {}
