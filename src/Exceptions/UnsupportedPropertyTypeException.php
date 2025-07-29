<?php

namespace Webgraphe\Phlux\Exceptions;

use RuntimeException;
use Webgraphe\Phlux\Contracts\PhluxException;

class UnsupportedPropertyTypeException extends RuntimeException implements PhluxException {}
