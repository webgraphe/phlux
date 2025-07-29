<?php

namespace Webgraphe\Phlux\Exceptions;

use RuntimeException;
use Webgraphe\Phlux\Contracts\PhluxException;

class UnsupportedClassException extends RuntimeException implements PhluxException {}
