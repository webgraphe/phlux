<?php

namespace Webgraphe\Phlux\Exceptions;

use RuntimeException;
use Webgraphe\Phlux\Contracts\PhluxException;

class UnknownClassException extends RuntimeException implements PhluxException {}
