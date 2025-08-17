<?php

namespace App\Controller\Api;

use App\Service\ServicesService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/services')]
class ServicesController extends AbstractController
{
    #[Route('/{action}/{service}', methods: ['GET'])]
    public function manage(string $action, string $service, ServicesService $servicesService): JsonResponse
    {
        try {
            $result = $servicesService->manageService($action, $service);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        return $this->json(['result' => $result]);
    }

    /**
     * Get status of multiple services at once
     * Example: /api/services/status?names=mysql,nginx,postgres
     */
    #[Route('/status', methods: ['GET'])]
    public function status(Request $request, ServicesService $servicesService): JsonResponse
    {
        $servicesName = $request->query->get('names', 'mysql,nginx,postgres');
        $services = [];
        foreach (explode(',', $servicesName) as $serviceName) {
            try {
                $status = $servicesService->manageService('status', $serviceName);
                $services[] = ['name' => $serviceName, 'status' => $status, 'success' => true];
            } catch (\Exception $e) {
                $services[] = ['name' => $serviceName, 'status' => $e->getMessage(), 'success' => false];
            }
        }

        return $this->json($services);
    }
}