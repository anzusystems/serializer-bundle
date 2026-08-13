<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Tests\Dto\EntityIdsDto;
use AnzuSystems\SerializerBundle\Tests\TestApp\Entity\Example;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;

final class EntityIdHandlerTest extends AbstractTestCase
{
    private const int FIRST_ID = 8_001;
    private const int SECOND_ID = 8_002;
    private const int THIRD_ID = 8_003;
    private const int MISSING_ID = 8_999;

    private EntityManagerInterface $entityManager;
    private DebugDataHolder $debugDataHolder;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->entityManager = $entityManager;
        /** @var DebugDataHolder $debugDataHolder */
        $debugDataHolder = self::getContainer()->get('doctrine.debug_data_holder');
        $this->debugDataHolder = $debugDataHolder;

        foreach ([self::FIRST_ID, self::SECOND_ID, self::THIRD_ID] as $id) {
            $this->entityManager->persist(new Example()->setId($id)->setName('example-' . $id));
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
        $this->debugDataHolder->reset();
    }

    protected function tearDown(): void
    {
        $this->entityManager->createQuery('DELETE FROM ' . Example::class)->execute();
        $this->entityManager->clear();

        parent::tearDown();
    }

    /**
     * @throws SerializerException
     */
    public function testListOfIdsIsFetchedInOneQuery(): void
    {
        $dto = $this->deserialize([self::FIRST_ID, self::SECOND_ID, self::THIRD_ID]);

        self::assertSame(1, $this->queryCount(), 'Three ids must cost one query, not one each');
        self::assertSame([self::FIRST_ID, self::SECOND_ID, self::THIRD_ID], $this->ids($dto));
    }

    /**
     * @throws SerializerException
     */
    public function testOrderFollowsTheRequestedIds(): void
    {
        $dto = $this->deserialize([self::THIRD_ID, self::FIRST_ID, self::SECOND_ID]);

        self::assertSame([self::THIRD_ID, self::FIRST_ID, self::SECOND_ID], $this->ids($dto));
    }

    /**
     * @throws SerializerException
     */
    public function testDuplicatesAreKeptAndUnknownIdsAreSkipped(): void
    {
        $dto = $this->deserialize([self::FIRST_ID, self::MISSING_ID, self::FIRST_ID]);

        self::assertSame([self::FIRST_ID, self::FIRST_ID], $this->ids($dto));
    }

    /**
     * @throws SerializerException
     */
    public function testUnknownIdsCostNoQueryOfTheirOwn(): void
    {
        $dto = $this->deserialize([self::FIRST_ID, self::MISSING_ID, self::SECOND_ID]);

        self::assertSame(1, $this->queryCount(), 'A missing id must not cost a query of its own');
        self::assertSame([self::FIRST_ID, self::SECOND_ID], $this->ids($dto));
    }

    /**
     * @throws SerializerException
     */
    public function testAlreadyLoadedIdsCostNoQuery(): void
    {
        $this->entityManager->getRepository(Example::class)
            ->findBy(['id' => [self::FIRST_ID, self::SECOND_ID, self::THIRD_ID]]);
        $this->debugDataHolder->reset();

        $dto = $this->deserialize([self::FIRST_ID, self::SECOND_ID, self::THIRD_ID]);

        self::assertSame(0, $this->queryCount());
        self::assertSame([self::FIRST_ID, self::SECOND_ID, self::THIRD_ID], $this->ids($dto));
    }

    /**
     * @throws SerializerException
     */
    public function testEmptyListCostsNoQuery(): void
    {
        $dto = $this->deserialize([]);

        self::assertSame(0, $this->queryCount());
        self::assertSame([], $this->ids($dto));
    }

    /**
     * @param list<int> $ids
     *
     * @throws SerializerException
     */
    private function deserialize(array $ids): EntityIdsDto
    {
        /** @var EntityIdsDto $dto */
        $dto = $this->serializer->deserialize(
            json_encode(['examples' => $ids], JSON_THROW_ON_ERROR),
            EntityIdsDto::class,
        );

        return $dto;
    }

    private function queryCount(): int
    {
        return count($this->debugDataHolder->getData()['default'] ?? []);
    }

    /**
     * @return list<int>
     */
    private function ids(EntityIdsDto $dto): array
    {
        return array_map(static fn (Example $example): int => $example->getId(), $dto->getExamples());
    }
}
