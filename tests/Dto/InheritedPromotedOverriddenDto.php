<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

final class InheritedPromotedOverriddenDto extends InheritedPromotedParentDto
{
    public function __construct(int $count = 20)
    {
        parent::__construct($count);
    }
}
