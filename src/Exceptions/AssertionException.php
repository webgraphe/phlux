<?php

namespace Webgraphe\Phlux\Exceptions;

use RuntimeException;
use Webgraphe\Phlux\Contracts\PhluxException;

class AssertionException extends RuntimeException implements PhluxException {}
