<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Handler\Handlers;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Metadata\Metadata;
use AnzuSystems\SerializerBundle\AnzuSystemsSerializerBundle;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(name: AnzuSystemsSerializerBundle::TAG_SERIALIZER_HANDLER)]
interface HandlerInterface
{
    public static function getPriority(): int;
    public static function supportsSerialize(mixed $value): bool;
    public static function supportsDeserialize(mixed $value, string $type): bool;
    public static function supportsDescribe(string $property, Metadata $metadata): bool;

    /**
     * @throws SerializerException
     */
    public function serialize(mixed $value, Metadata $metadata): mixed;

    /**
     * @throws SerializerException
     */
    public function deserialize(mixed $value, Metadata $metadata): mixed;

    public function describe(string $property, Metadata $metadata): array;
}
