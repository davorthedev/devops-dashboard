<?php

namespace App\Controller\Api;

use App\Service\MetricsService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/metrics')]
class MetricsController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(MetricsService $metricsService): JsonResponse
    {
        try {
            return $this->json($metricsService->getMetrics(), JsonResponse::HTTP_OK, ['Cache-Control' => 'no-store']);
        } catch (\Throwable $e) {
            return $this->json(
                ['error' => 'Failed to collect metrics'],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get metric history for last 24 hours.
     *
     * Optional query parameter:
     *  - from (string, format "Y-m-d H:i:s"): If provided, only metrics created on or after this date are returned.
     *
     * Examples:
     *  GET /api/metrics/history
     *  GET /api/metrics/history?from=2025-08-01 12:00:00
     */
    #[Route('/history', methods: ['GET'])]
    public function getHistory(Request $request, MetricsService $metricsService): JsonResponse
    {
        try {
            $data = $metricsService->getMetricsFromDate($request->query->get('from'));
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        return $this->json($data);
    }
}