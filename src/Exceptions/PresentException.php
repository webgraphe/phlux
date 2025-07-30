<?php

declare(strict_types=1);

namespace Webgraphe\Phlux\Exceptions;

use RuntimeException;
use Webgraphe\Phlux\Attributes\Present;
use Webgraphe\Phlux\Contracts\PhluxException;

/**
 * Flow-control exception thrown when there's no data to unmarshal with a {@see Present} attribute
 */
class PresentException extends RuntimeException implements PhluxException {}
