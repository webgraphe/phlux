<?php

namespace Webgraphe\Phlux\Exceptions;

use RuntimeException;
use Webgraphe\Phlux\Contracts\PhluxException;

class DiscriminatorException extends RuntimeException implements PhluxException {}
