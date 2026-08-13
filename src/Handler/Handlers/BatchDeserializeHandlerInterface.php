<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Handler\Handlers;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Handler\BatchItem;

interface BatchDeserializeHandlerInterface extends HandlerInterface
{
    /**
     * @throws SerializerException
     */
    public function prepareDeserializeBatch(BatchItem ...$items): void;
}
