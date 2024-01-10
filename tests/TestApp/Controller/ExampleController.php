<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\TestApp\Controller;

use AnzuSystems\SerializerBundle\Attributes\SerializeParam;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Serializer;
use AnzuSystems\SerializerBundle\Tests\TestApp\Entity\Example;
use AnzuSystems\SerializerBundle\Tests\TestApp\Entity\ExampleItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/example')]
final class ExampleController extends AbstractController
{
    public function __construct(
        private readonly Serializer $serializer,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws SerializerException
     */
    #[Route('', 'create_example', methods: [Request::METHOD_POST])]
    public function createExample(#[SerializeParam] Example $example): JsonResponse
    {
        $this->entityManager->persist($example);
        $this->entityManager->flush();

        return new JsonResponse($this->serializer->toArray($example), JsonResponse::HTTP_CREATED);
    }

    /**
     * @throws SerializerException
     */
    #[Route('/item', 'create_example_item', methods: [Request::METHOD_POST])]
    public function createExampleItem(#[SerializeParam] ExampleItem $exampleItem): JsonResponse
    {
        $this->entityManager->persist($exampleItem);
        $this->entityManager->flush();

        return new JsonResponse($this->serializer->toArray($exampleItem), JsonResponse::HTTP_CREATED);
    }

    /**
     * @throws SerializerException
     */
    #[Route('/{example}', 'get_one_example', ['example' => '\d+'], methods: [Request::METHOD_GET])]
    public function getOneExample(Example $example): JsonResponse
    {
        return new JsonResponse($this->serializer->toArray($example));
    }
}
