<?php

namespace App\Controller\Api;

use App\Service\LogsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/logs')]
class LogsController extends AbstractController
{
    // TODO proveriti sigrnost i da li vracati json kao obkeat ili niz, ili kako??

    #[Route('', methods: ['GET'])]
    public function index(LogsService $logsService): JsonResponse
    {
        return $this->json($logsService->getAnomalies());
    }
}