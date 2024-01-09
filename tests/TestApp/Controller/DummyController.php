<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\TestApp\Controller;

use AnzuSystems\SerializerBundle\Attributes\SerializeParam;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Serializer;
use AnzuSystems\SerializerBundle\Tests\TestApp\Entity\Example;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dummy')]
final class DummyController extends AbstractController
{
    public function __construct(
        private readonly Serializer $serializer,
    ) {
    }

    #[Route('/ok', methods: [Request::METHOD_GET])]
    public function okTest(): JsonResponse
    {
        return new JsonResponse(['ok']);
    }

    /**
     * @throws SerializerException
     */
    #[Route('/value-resolver', methods: [Request::METHOD_POST])]
    public function valueResolverPostTest(#[SerializeParam] Example $example): JsonResponse
    {
        return new JsonResponse($this->serializer->toArray($example));
    }

    /**
     * @throws SerializerException
     */
    #[Route('/value-resolver', methods: [Request::METHOD_GET])]
    public function valueResolverGetTest(#[SerializeParam] Example $example): JsonResponse
    {
        return new JsonResponse($this->serializer->toArray($example));
    }
}
