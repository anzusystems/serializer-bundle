<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Handler\HandlerResolver;
use AnzuSystems\SerializerBundle\Handler\Handlers\BasicHandler;
use AnzuSystems\SerializerBundle\Handler\Handlers\DateTimeHandler;
use AnzuSystems\SerializerBundle\Handler\Handlers\EnumHandler;
use AnzuSystems\SerializerBundle\Handler\Handlers\ObjectHandler;
use AnzuSystems\SerializerBundle\Handler\Handlers\UuidHandler;
use AnzuSystems\SerializerBundle\Tests\TestApp\Model\ExampleBackedEnum;
use AnzuSystems\SerializerBundle\Tests\TestApp\Serializer\NonSharedHandler;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Symfony\Component\Uid\Uuid;

final class HandlerResolverTest extends AbstractTestCase
{
    /**
     * @param list<array{mixed, class-string}> $valuesAndHandlers
     *
     * @throws SerializerException
     */
    #[DataProvider('serializationDataProvider')]
    public function testSerializationHandlerFollowsTheValueType(array $valuesAndHandlers): void
    {
        $resolver = self::getContainer()->get(HandlerResolver::class);

        foreach ([1, 2] as $round) {
            foreach ($valuesAndHandlers as [$value, $expectedHandler]) {
                self::assertInstanceOf($expectedHandler, $resolver->getSerializationHandler($value, null), sprintf('round %d', $round));
            }
        }
    }

    public static function serializationDataProvider(): array
    {
        return [
            'nullable object' => [[[null, BasicHandler::class], [new DateTimeImmutable(), DateTimeHandler::class]]],
            'scalars and objects' => [[
                ['text', BasicHandler::class],
                [Uuid::v7(), UuidHandler::class],
                [ExampleBackedEnum::First, EnumHandler::class],
                [new stdClass(), ObjectHandler::class],
                [[1, 2], ObjectHandler::class],
                [1, BasicHandler::class],
            ]],
        ];
    }

    /**
     * @param list<array{mixed, string, class-string}> $valuesAndHandlers
     *
     * @throws SerializerException
     */
    #[DataProvider('deserializationDataProvider')]
    public function testDeserializationHandlerFollowsTheValueAndTargetType(array $valuesAndHandlers): void
    {
        $resolver = self::getContainer()->get(HandlerResolver::class);

        foreach ([1, 2] as $round) {
            foreach ($valuesAndHandlers as [$value, $type, $expectedHandler]) {
                self::assertInstanceOf($expectedHandler, $resolver->getDeserializationHandler($value, $type, null), sprintf('round %d', $round));
            }
        }
    }

    public static function deserializationDataProvider(): array
    {
        return [
            'nullable date' => [[
                [null, DateTimeImmutable::class, BasicHandler::class],
                ['2026-10-13T12:00:00.000000Z', DateTimeImmutable::class, DateTimeHandler::class],
            ]],
            'same value type, other target' => [[
                ['first', ExampleBackedEnum::class, EnumHandler::class],
                ['text', 'string', BasicHandler::class],
                [['a' => 1], stdClass::class, ObjectHandler::class],
            ]],
        ];
    }

    /**
     * @throws SerializerException
     */
    public function testCustomHandlerWinsOverTheRememberedOne(): void
    {
        $resolver = self::getContainer()->get(HandlerResolver::class);
        $resolver->getSerializationHandler(new DateTimeImmutable(), null);

        self::assertInstanceOf(ObjectHandler::class, $resolver->getSerializationHandler(new DateTimeImmutable(), ObjectHandler::class));
    }

    /**
     * @throws SerializerException
     */
    public function testNonSharedHandlerIsFetchedForEveryValue(): void
    {
        $resolver = self::getContainer()->get(HandlerResolver::class);

        self::assertNotSame(
            $resolver->getSerializationHandler('first', NonSharedHandler::class),
            $resolver->getSerializationHandler('second', NonSharedHandler::class),
        );
        self::assertSame($resolver->getSerializationHandler('first', null), $resolver->getSerializationHandler('second', null));
    }
}
