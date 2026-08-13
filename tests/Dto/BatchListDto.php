<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class BatchListDto
{
    /**
     * @var list<BatchDto>
     */
    #[Serialize(type: BatchDto::class)]
    private array $data;

    /**
     * @param list<BatchDto> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return list<BatchDto>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
