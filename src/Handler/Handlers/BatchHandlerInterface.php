<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Handler\Handlers;

use AnzuSystems\SerializerBundle\Context\SerializationContext;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Metadata\Metadata;

interface BatchHandlerInterface extends HandlerInterface
{
    /**
     * @param list<mixed> $values
     *
     * @throws SerializerException
     */
    public function prepareSerializeBatch(array $values, Metadata $metadata, SerializationContext $context): void;
}
