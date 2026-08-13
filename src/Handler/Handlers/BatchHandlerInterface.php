<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Handler\Handlers;

use AnzuSystems\SerializerBundle\Context\SerializationContext;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Metadata\Metadata;

interface BatchHandlerInterface extends HandlerInterface
{
    /**
     * @param list<mixed> $values every value the following serialize() calls will receive, for one property
     *
     * @throws SerializerException
     */
    public function prepareSerializeBatch(array $values, Metadata $metadata, SerializationContext $context): void;
}
