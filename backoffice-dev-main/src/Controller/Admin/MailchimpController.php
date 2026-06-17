<?php

namespace App\Controller\Admin;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mailchimp')]
#[IsGranted('ROLE_OPERATIONS')]
class MailchimpController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private \MailchimpTransactional\ApiClient $mailchimpApi,
        private string $transactionalSenderEmail,
    ) {}

    #[Route('/rejects', name: 'admin_mailchimp_rejectlist_index', methods: ['GET'])]
    public function rejectionList(Request $request): Response
    {
        $this->logger->debug('List mandrill rejection denylist');
        try {
            // Note that the Mailchimp-transaction client library is using dynamic properties
            // See https://github.com/mailchimp/mailchimp-client-lib-codegen/pull/328 for eventual fix
            $response = $this->mailchimpApi->rejects->list();
            if (is_array($response) || $response instanceof \stdClass) {
                $results = json_decode(json_encode($response), true);
            }
            if ($response instanceof \GuzzleHttp\Exception\RequestException) {
                $this->logger->error(
                    'Mailchimp api error '
                    . (string) $response->getResponse()->getBody(),
                );
                $this->addFlash('error', (string) $response->getResponse()->getBody());
            }
        } catch (\Exception $e) {
            $this->logger->error('Mailchimp api error ' . $e->getMessage());
            $this->addFlash('error', $e->getMessage());
        }
        return $this->render('admin/pages/mailchimp/rejects/list.html.twig', [
            'results' => $results ?? [],
        ]);
    }

    #[Route(
        '/rejects/delete/{email}',
        name: 'admin_mailchimp_rejectlist_delete',
        methods: ['GET', 'POST'],
    )]
    public function removeRejection(Request $request, string $email): Response
    {
        $form = $this
            ->createFormBuilder()
            ->add('confirmation', CheckboxType::class, [
                'label' => 'I confirm that the user has added our email to their allowlist/whitelist',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Confirm Removal from Denylist',
            ])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->logger->debug('Remove email from mandrill rejection denylist');
                // Note that the Mailchimp-transaction client library is using dynamic properties
                // See https://github.com/mailchimp/mailchimp-client-lib-codegen/pull/328 for eventual fix
                $response = $this->mailchimpApi->rejects->delete([
                    'email' => $email,
                ]);
                if (is_array($response) || $response instanceof \stdClass) {
                    $this->addFlash(
                        'success',
                        "Successfully removed from rejection denylist: {$email}",
                    );
                    return $this->redirectToRoute(
                        'admin_mailchimp_rejectlist_index',
                        [],
                        Response::HTTP_SEE_OTHER,
                    );
                }
                if ($response instanceof \GuzzleHttp\Exception\RequestException) {
                    $this->logger->error(
                        'Mailchimp api error '
                        . (string) $response->getResponse()->getBody(),
                    );
                    $this->addFlash(
                        'error',
                        (string) $response->getResponse()->getBody(),
                    );
                }
            } catch (\Exception $e) {
                $this->logger->error('Mailchimp api error ' . $e->getMessage());
                $this->addFlash('error', $e->getMessage());
            }
        }
        return $this->render('admin/pages/mailchimp/rejects/delete.html.twig', [
            'email' => $email,
            'transactionalSenderEmail' => $this->transactionalSenderEmail,
            'form' => $form->createView(),
        ]);
    }
}
