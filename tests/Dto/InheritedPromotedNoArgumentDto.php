<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

final class InheritedPromotedNoArgumentDto extends InheritedPromotedParentDto
{
    public function __construct()
    {
        parent::__construct(30);
    }
}
