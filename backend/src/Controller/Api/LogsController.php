<?php

namespace App\Controller\Api;

use App\Service\LogsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/logs')]
class LogsController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(LogsService $logsService): JsonResponse
    {
        try {
            return $this->json($logsService->getAnomalies(), JsonResponse::HTTP_OK, ['Cache-Control' => 'no-store']);
        } catch (\Throwable $e) {
            return $this->json(
                ['error' => 'Failed to fetch log anomalies'],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}