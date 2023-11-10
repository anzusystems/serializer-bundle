<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\TestApp\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dummy')]
final class DummyController extends AbstractController
{
    #[Route('/ok', methods: [Request::METHOD_GET])]
    public function okTest(): JsonResponse
    {
        return new JsonResponse(['ok']);
    }
}
