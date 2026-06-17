<?php

namespace App\Controller\Admin;

use App\Entity\UserClient;
use App\Form\Type\UserClientType;
use App\Service\Manager\UserClientManager;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/administration')]
#[IsGranted('ROLE_ANALYST')]
class ApiClientController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private UserClientManager $userClientManager,
        private array $oAuth2Scopes,
    ) {}

    #[Route('/clients', name: 'admin_administration_clients', methods: ['GET'])]
    public function clientIndex(): Response
    {
        // $this->logger->debug('View API clients list');
        return $this->render('admin/pages/administration/client_list.html.twig', [
            'userClients' => $this->userClientManager->listClients(),
        ]);
    }

    #[Route('/clients/add', name: 'admin_administration_clients_add', methods: ['GET'])]
    #[IsGranted('ROLE_TECH_OPS')]
    public function clientAdd(): Response
    {
        $this->logger->debug('Add new API client');
        return $this->render('admin/pages/administration/client_new.html.twig', [
            'userClient' => $this->userClientManager->createClient(),
        ]);
    }

    #[Route(
        '/clients/{identifier}/delete',
        name: 'admin_administration_clients_delete',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_TECH_OPS')]
    public function clientDelete(Request $request, string $identifier): Response
    {
        $client = $this->userClientManager->findClient(['client' => $identifier]);
        if (is_null($client)) {
            $this->addFlash(
                'error',
                "Client with identifier: {$identifier} could not be found",
            );
            return $this->redirectToRoute('admin_administration_clients');
        }
        if ($client->getClient()->isActive() && !$request->query->get('new', false)) {
            $this->addFlash(
                'error',
                "Client with identifier: {$identifier} is active and cannot be deleted. You must deactivate the client before deleting.",
            );
            return $this->redirectToRoute('admin_administration_clients');
        }

        if ($this->userClientManager->deleteClient($identifier)) {
            $this->addFlash('success', 'Client successfully deleted: ' . $identifier);
            $this->logger->debug('Deleting API client: ' . $identifier);
        } else {
            $this->addFlash(
                'error',
                "Client with identifier: {$identifier} could not be deleted",
            );
        }
        return $this->redirectToRoute('admin_administration_clients');
    }

    #[Route(
        '/clients/{identifier}',
        name: 'admin_administration_clients_edit',
        methods: ['GET', 'POST'],
    )]
    public function clientEdit(Request $request, string $identifier): Response
    {
        // $this->logger->debug('Editing API client');

        /** @var \Doctrine\ORM\EntityRepository */
        $repository = $this->doctrine->getManager()->getRepository(UserClient::class);

        /** @var UserClient|null $userClient */
        $userClient = $repository->findOneBy(['client' => $identifier]);

        if (!$userClient) {
            $this->addFlash('warning', 'Could not find Client: ' . $identifier);
            return $this->redirectToRoute('admin_administration_clients');
        }

        $form = $this->createForm(UserClientType::class, $userClient, [
            'read_only' => !$this->isGranted('ROLE_TECH_OPS'),
            'oauth_scopes' => $this->oAuth2Scopes,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->userClientManager->updateClient($userClient)) {
                $this->addFlash(
                    'success',
                    'Client successfully updated: ' . $identifier,
                );
                $this->logger->debug('Updated API client: ' . $identifier);
            } else {
                $this->addFlash(
                    'error',
                    'An error occured when trying to update Client: ' . $identifier,
                );
            }
            return $this->redirectToRoute('admin_administration_clients');
        }

        return $this->render('admin/pages/administration/client_edit.html.twig', [
            'userClient' => $userClient,
            'form' => $form->createView(),
        ]);
    }
}
