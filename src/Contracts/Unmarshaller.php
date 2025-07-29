<?php

namespace Webgraphe\Phlux\Contracts;

use Webgraphe\Phlux\Exceptions\PresentException;

interface Unmarshaller
{
    /**
     * @throws PresentException
     */
    public function __invoke(mixed $value = null): mixed;
}
