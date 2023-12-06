<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Event;

use AnzuSystems\SerializerBundle\Metadata\ClassMetadata;
use Symfony\Contracts\EventDispatcher\Event;

final class LoadMetadataEvent extends Event
{
    public const NAME = 'anzu_systems_serializer.load_metadata';

    public function __construct(
        protected ClassMetadata $classMetadata,
    ) {
    }

    public function getClassMetadata(): ClassMetadata
    {
        return $this->classMetadata;
    }
}
