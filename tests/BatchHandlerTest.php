<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests;

use AnzuSystems\SerializerBundle\Context\SerializationContext;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Tests\Dto\BatchDto;
use AnzuSystems\SerializerBundle\Tests\Dto\BatchListDto;
use AnzuSystems\SerializerBundle\Tests\Dto\BatchOtherDto;
use AnzuSystems\SerializerBundle\Tests\TestApp\Serializer\RecordingBatchHandler;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

final class BatchHandlerTest extends AbstractTestCase
{
    private const string COLLECTION_JSON = '[{"code":"first","label":"First"},{"code":"second","label":"Second"},{"code":"third","label":"Third"}]';

    private RecordingBatchHandler $handler;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var RecordingBatchHandler $handler */
        $handler = self::getContainer()->get(RecordingBatchHandler::class);
        $this->handler = $handler;
    }

    /**
     * @param list<list<mixed>> $expectedBatches
     *
     * @throws SerializerException
     */
    #[DataProvider('data')]
    public function testBatchIsPreparedWithoutChangingTheOutput(
        object|iterable $data,
        array $expectedBatches,
        string $expectedJson,
    ): void {
        $serialized = $this->serializer->serialize($data);

        self::assertSame($expectedBatches, $this->handler->getBatches());
        self::assertSame($expectedJson, $serialized);
    }

    public static function data(): iterable
    {
        $values = [['first', 'second', 'third']];

        yield 'array' => [self::items(), $values, self::COLLECTION_JSON];
        yield 'collection' => [new ArrayCollection(self::items()), $values, self::COLLECTION_JSON];
        yield 'collection inside an object' => [
            new BatchListDto(self::items()),
            $values,
            '{"data":' . self::COLLECTION_JSON . '}',
        ];
        yield 'single object' => [new BatchDto('first', 'First'), [], '{"code":"first","label":"First"}'];
        yield 'generator' => [self::itemGenerator(), [], self::COLLECTION_JSON];
        yield 'duplicate values' => [
            [new BatchDto('same', 'One'), new BatchDto('same', 'Two')],
            [['same', 'same']],
            '[{"code":"same","label":"One"},{"code":"same","label":"Two"}]',
        ];
    }

    /**
     * @throws SerializerException
     */
    public function testEachSerializedCollectionGetsItsOwnBatch(): void
    {
        $this->serializer->serialize(self::items());
        $this->serializer->serialize([new BatchDto('fourth', 'Fourth')]);

        self::assertSame([['first', 'second', 'third'], ['fourth']], $this->handler->getBatches());
    }

    /**
     * @throws SerializerException
     */
    public function testPropertiesOfDifferentClassesShareOneBatchAndKeepTheirMetadata(): void
    {
        $this->serializer->serialize([
            new BatchDto('first', 'First'),
            new BatchOtherDto('other'),
            new BatchDto('second', 'Second'),
        ]);

        self::assertSame([['first', 'other', 'second']], $this->handler->getBatches());
        self::assertSame([['code', 'ref', 'code']], $this->handler->getBatchedProperties());
    }

    /**
     * @throws SerializerException
     */
    public function testBatchedValueIsReadFromTheGetterOnlyOnce(): void
    {
        $item = new BatchDto('first', 'First');
        $this->serializer->serialize([$item]);

        self::assertSame(1, $item->getCodeReads());
    }

    /**
     * @throws SerializerException
     */
    public function testBatchReceivesTheSerializationContext(): void
    {
        $this->serializer->serialize(self::items(), SerializationContext::create()->setSerializeNulls(false));

        self::assertSame([false], $this->handler->getBatchedSerializeNulls());
    }

    /**
     * @return list<BatchDto>
     */
    private static function items(): array
    {
        return [
            new BatchDto('first', 'First'),
            new BatchDto('second', 'Second'),
            new BatchDto('third', 'Third'),
        ];
    }

    /**
     * @return Generator<int, BatchDto>
     */
    private static function itemGenerator(): Generator
    {
        yield from self::items();
    }
}
