<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Request\ParamConverter;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Serializer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @deprecated Use SerializerValueResolver::class instead.
 */
final class SerializerParamConverter implements ParamConverterInterface
{
    public function __construct(
        private readonly Serializer $serializer
    ) {
    }

    /**
     * @throws SerializerException
     */
    public function apply(Request $request, ParamConverter $configuration): bool
    {
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        if ($configuration->isOptional()
            && empty($request->getContent())
            && false === $request->isMethod(Request::METHOD_GET)
        ) {
            return false;
        }
        /** @psalm-var class-string $class */
        $class = $configuration->getClass();
        $type = $configuration->getOptions()['type'] ?? $class;
        $request->attributes->set($configuration->getName(), $this->getValue($request, $type));

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return (bool) $configuration->getClass();
    }

    /**
     * @template T
     *
     * @param class-string<T> $type
     *
     * @return T
     *
     * @throws SerializerException
     *
     * @psalm-suppress all
     */
    private function getValue(Request $request, string $type): object
    {
        if ($request->isMethod(Request::METHOD_GET)) {
            return $this->serializer->fromArray($request->query->all(), $type);
        }
        $content = (string) $request->getContent();
        if (empty($content)) {
            return new $type();
        }

        return $this->serializer->deserialize($content, $type);
    }
}
