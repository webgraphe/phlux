<?php

declare(strict_types=1);

namespace Webgraphe\Phlux\Exceptions;

use RuntimeException;
use Webgraphe\Phlux\Contracts\PhluxException;

class UnknownClassException extends RuntimeException implements PhluxException {}
