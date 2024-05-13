<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Context;

final class SerializationContext
{
    private bool $serializeNulls = true;

    public function setSerializeNulls(bool $serializeNulls): self
    {
        $this->serializeNulls = $serializeNulls;

        return $this;
    }

    /**
     * Returns true when NULLs should be serialized.
     */
    public function shouldSerializeNull(): bool
    {
        return $this->serializeNulls;
    }

    public static function create(): self
    {
        return new self();
    }
}
