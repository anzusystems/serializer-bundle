<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Handler\Handlers;

use AnzuSystems\SerializerBundle\Exception\DeserializationException;
use AnzuSystems\SerializerBundle\Metadata\Metadata;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Symfony\Component\PropertyInfo\Type;

final class DateTimeHandler extends AbstractHandler
{
    public function __construct(
        private readonly string $serializerDateFormat,
    ) {
    }

    public static function supportsSerialize(mixed $value): bool
    {
        return $value instanceof DateTimeInterface;
    }

    /**
     * @param DateTimeInterface $value
     */
    public function serialize(mixed $value, Metadata $metadata): string
    {
        return $value->format($metadata->customType ?? $this->serializerDateFormat);
    }

    public static function supportsDeserialize(mixed $value, string $type): bool
    {
        return is_a($type, DateTimeInterface::class, true);
    }

    public function deserialize(mixed $value, Metadata $metadata): ?DateTimeInterface
    {
        if (null === $value) {
            return null;
        }
        /** @var class-string<DateTime|DateTimeImmutable> $dateClass */
        $dateClass = $metadata->type;
        $format = $metadata->customType ?? $this->serializerDateFormat;
        $date = $dateClass::createFromFormat($format, $value);
        if ($date) {
            return $date->setTimezone(new DateTimeZone('UTC'));
        }

        throw new DeserializationException(sprintf(
            'Unable to create DateTime from format "%s" with value "%s".',
            $format,
            $value
        ));
    }

    public static function supportsDescribe(string $property, Metadata $metadata): bool
    {
        return is_a($metadata->type, DateTimeInterface::class, true);
    }

    public function describe(string $property, Metadata $metadata): array
    {
        $description = parent::describe($property, $metadata);
        $description['type'] = Type::BUILTIN_TYPE_STRING;
        $description['format'] = 'date-time, format: "' . ($metadata->customType ?? $this->serializerDateFormat) . '"';

        return $description;
    }
}
