<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Handler\Handlers;

use AnzuSystems\SerializerBundle\Context\SerializationContext;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Handler\BatchItem;

interface BatchSerializeHandlerInterface extends HandlerInterface
{
    /**
     * @throws SerializerException
     */
    public function prepareSerializeBatch(SerializationContext $context, BatchItem ...$items): void;
}
