<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Handler\Handlers;

use AnzuSystems\SerializerBundle\Exception\SerializerException;

interface BatchHandlerInterface extends HandlerInterface
{
    /**
     * @param list<mixed> $values
     *
     * @throws SerializerException
     */
    public function prepareSerializeBatch(array $values): void;
}
