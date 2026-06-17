<?php

namespace ClientBundle\Service;

use AppBundle\Entity\Enum\ScaStatus;
use ClientBundle\Service\Yielders\ApiClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Service for support Mangopay SCA (Secure Customer Authentication actions)
 */
class ScaService
{
    public function __construct(
        private ApiClient $client,
        private LoggerInterface $logger,
        private RequestStack $requestStack,
    ) {
    }

    public function canScaEnroll(array $userInfo): bool
    {
        $scaStatus = $userInfo['sca_status'] ?? ScaStatus::Inactive->value;
        $this->logger->debug("User SCA status is {$scaStatus}");
        if ($scaStatus != ScaStatus::Active->value) {
            return true;
        }
        return false;
    }

    public function startScaEnrollmentSession(string $returnUrl): string
    {
        $this->logger->debug("Starting new SCA enrollment session");
        $response = $this->client->authenticatedUser()->createScaEnrollment();
        if (!($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            $this->logger->error(
                "Status code: {$response->getStatusCode()}. Response: ",
                $this->client->getContent($response)
            );
            throw new BadRequestHttpException('Unable to start new SCA enrollment session');
        }
        $responseContent = $this->client->getContent($response);
        $queryParams = http_build_query([
            'returnUrl' => $returnUrl
        ]);
        $this->logger->debug("SCA session start response", [$responseContent]);
        return $responseContent['PendingUserAction']['RedirectUrl'] . "&{$queryParams}";
    }

    public function processScaEnrollmentResult(bool $success): ?array
    {
        $this->logger->debug("Updating SCA status based on SCA enrollment result");
        $scaStatus = $success ? ScaStatus::Active : ScaStatus::Inactive;
        $response = $this->client->authenticatedUser()->updateScaStatus([
            'json' => [
                'status' => $scaStatus->value
            ]
        ]);
        if (!($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            $this->logger->error(
                "Status code: {$response->getStatusCode()}. Response: ",
                $this->client->getContent($response)
            );
            throw new BadRequestHttpException('Unable to update user SCA status');
        }
        return $this->client->getContent($response);
    }

    public function isScaSuccess(?string $controlStatus): bool
    {
        $this->logger->debug("SCA indicative result:", ["controlStatus" => $controlStatus]);
        return $controlStatus == 'VALIDATED';
    }

    public function shouldVerify(?string $controlStatus): bool
    {
        // Special case where PENDING_VALIDATION is on either of the indicators
        // Which suggests the SCA session ended prematurely, and we should mark this as a fail
        // $this->logger->debug(
        //     "Should verify:",
        //     [$controlStatus != 'PENDING_VALIDATION']
        // );
        return $controlStatus != 'PENDING_VALIDATION';
    }
}
