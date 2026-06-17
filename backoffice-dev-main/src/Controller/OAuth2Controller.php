<?php

namespace App\Controller;

use App\Dto\OAuth2LogoutQueryDto;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class OAuth2Controller extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private TokenStorageInterface $tokenStorage,
    ) {}

    #[Route(path: '/oauth2/logout')]
    public function oauth2LogoutAction(
        Request $request,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)]
        OAuth2LogoutQueryDto $dto,
    ): RedirectResponse {
        $continueUrl = $dto->continue_url;
        $this->logger->info('IN oauth2LogoutAction. Redirect to ' . $continueUrl);

        $this->tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        return $this->redirect($continueUrl);
    }
}
