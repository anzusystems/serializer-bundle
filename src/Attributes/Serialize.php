<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Attributes;

use AnzuSystems\SerializerBundle\Metadata\ContainerParam;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class Serialize
{
    public const KEYS_VALUES = 'kv';

    public function __construct(
        public ?string $serializedName = null,
        public ?string $handler = null,
        public null|string|ContainerParam $type = null,
        public ?string $strategy = null,
        public ?string $persistedName = null,
    ) {
    }
}
