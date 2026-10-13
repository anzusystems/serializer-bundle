<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Order;

final class OrderedBarsDto
{
    /**
     * @param ArrayCollection<int, BarDto> $barsByEnum
     * @param ArrayCollection<int, BarDto> $barsByString
     */
    public function __construct(
        #[Serialize(type: BarDto::class, orderBy: ['qux' => Order::Descending])]
        private ArrayCollection $barsByEnum,
        #[Serialize(type: BarDto::class, orderBy: ['qux' => 'ASC'])]
        private ArrayCollection $barsByString,
    ) {
    }

    /**
     * @return ArrayCollection<int, BarDto>
     */
    public function getBarsByEnum(): ArrayCollection
    {
        return $this->barsByEnum;
    }

    /**
     * @return ArrayCollection<int, BarDto>
     */
    public function getBarsByString(): ArrayCollection
    {
        return $this->barsByString;
    }
}
