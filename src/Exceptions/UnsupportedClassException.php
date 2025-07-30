<?php

declare(strict_types=1);

namespace Webgraphe\Phlux\Exceptions;

use RuntimeException;
use Webgraphe\Phlux\Contracts\PhluxException;

class UnsupportedClassException extends RuntimeException implements PhluxException {}
