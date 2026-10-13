<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Tests\Dto\BarDto;
use AnzuSystems\SerializerBundle\Tests\Dto\OrderedBarsDto;
use Doctrine\Common\Collections\ArrayCollection;

final class OrderBySerializeTest extends AbstractTestCase
{
    /**
     * @throws SerializerException
     */
    public function testSelectableIsSerializedInTheAttributeOrder(): void
    {
        $dto = new OrderedBarsDto(new ArrayCollection([new BarDto(1, 'a'), new BarDto(3, 'c'), new BarDto(2, 'b')]));

        self::assertSame(
            '{"bars":[{"qux":3,"quux":"c"},{"qux":2,"quux":"b"},{"qux":1,"quux":"a"}]}',
            $this->serializer->serialize($dto),
        );
    }
}
