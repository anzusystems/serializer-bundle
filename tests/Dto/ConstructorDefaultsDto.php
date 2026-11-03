<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class ConstructorDefaultsDto
{
    public const string DEFAULT_NOTE = 'default note';
    public const int DEFAULT_COUNT = 10;

    public function __construct(
        #[Serialize]
        public private(set) string $name,
        #[Serialize]
        public private(set) ?string $label,
        #[Serialize]
        public private(set) int $count = self::DEFAULT_COUNT,
        #[Serialize]
        public private(set) ?string $note = self::DEFAULT_NOTE,
    ) {
    }
}
