<?php

namespace App\Controller\ApiV2;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER', statusCode: 401, message: 'Must be logged in as a user')]
class MeController extends AbstractFOSRestController
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    #[Route(path: '/me', methods: ['GET'])]
    #[IsGranted('ROLE_OAUTH2_USER:READ')]
    public function getCurrentUser(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_OAUTH2_USER:READ');
        $context = new Context();
        $context->addGroups(['standard', 'user']);
        $user = $this->getUser();
        $view = View::create()->setData($user)->setContext($context);
        return $this->handleView($view);
    }
}
