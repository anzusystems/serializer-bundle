<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use AnzuSystems\SerializerBundle\Handler\Handlers\ArrayStringHandler;
use AnzuSystems\SerializerBundle\Tests\TestApp\Model\ExampleBackedEnum;
use AnzuSystems\SerializerBundle\Tests\TestApp\Model\ExampleIntBackedEnum;
use AnzuSystems\SerializerBundle\Tests\TestApp\Model\ExampleUnitEnum;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final class NonScalarTypesDto
{
    #[Serialize]
    public array $array = [];

    #[Serialize]
    public DateTimeImmutable $date;

    #[Serialize]
    public Uuid $uuid;

    #[Serialize]
    public ExampleBackedEnum $enum;

    #[Serialize]
    public ExampleIntBackedEnum $intEnum;

    #[Serialize]
    public ExampleUnitEnum $unitEnum;

    #[Serialize(handler: ArrayStringHandler::class, type: 'int')]
    public array $ids = [];
}
