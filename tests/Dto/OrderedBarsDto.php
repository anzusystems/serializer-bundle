<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use Doctrine\Common\Collections\ArrayCollection;

final class OrderedBarsDto
{
    /**
     * @param ArrayCollection<int, BarDto> $bars
     */
    public function __construct(
        #[Serialize(type: BarDto::class, orderBy: ['qux' => 'DESC'])]
        private ArrayCollection $bars,
    ) {
    }

    /**
     * @return ArrayCollection<int, BarDto>
     */
    public function getBars(): ArrayCollection
    {
        return $this->bars;
    }
}
