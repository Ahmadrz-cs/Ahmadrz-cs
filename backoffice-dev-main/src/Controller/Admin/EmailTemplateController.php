<?php

namespace App\Controller\Admin;

use App\Entity\Mail;
use App\Form\Type\MailParametersType;
use App\Form\Type\MailType;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/email-templates')]
#[IsGranted('ROLE_TECH_OPS')]
class EmailTemplateController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private MailerService $mailerService,
    ) {}

    #[Route(path: '/legacy', name: 'admin_email_templates_legacy_list')]
    public function legacyEmailTemplates(Request $request): Response
    {
        $filters = [
            'perPage' => 10,
            'page' => $request->query->get('page', 1),
        ];
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->select('m')->from(Mail::class, 'm')->addOrderBy('m.id', 'DESC');
        $adapter = new QueryAdapter($queryBuilder);
        $results = new Pagerfanta($adapter);
        $results->setMaxPerPage($filters['perPage'] ?? 10);
        $results->setCurrentPage(min($filters['page'] ?? 1, $results->getNbPages()));
        return $this->render('admin/pages/email_templates/legacy/list.html.twig', [
            'results' => $results ?? [],
        ]);
    }

    #[Route(path: '/legacy/create', name: 'admin_email_templates_legacy_create')]
    public function legacyEmailTemplateCreate(Request $request): Response
    {
        $emailTemplate = new Mail();
        $form = $this->createForm(MailType::class, $emailTemplate);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($emailTemplate);
            $this->entityManager->flush();
            $this->addFlash('success', 'Successfully created email template');
            return $this->redirectToRoute(
                'admin_email_templates_legacy_edit',
                ['id' => $emailTemplate->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/email_templates/legacy/create.html.twig', [
            'emailTemplate' => $emailTemplate,
            'slugSuggestions' => $this->mailerService->getSupportedMailTypes(),
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/legacy/{id}', name: 'admin_email_templates_legacy_edit')]
    public function legacyEmailTemplateEdit(
        Request $request,
        Mail $emailTemplate,
    ): Response {
        $form = $this->createForm(MailType::class, $emailTemplate);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Successfully updated email template');
            return $this->redirectToRoute(
                'admin_email_templates_legacy_edit',
                ['id' => $emailTemplate->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/email_templates/legacy/edit.html.twig', [
            'emailTemplate' => $emailTemplate,
            'slugSuggestions' => $this->mailerService->getSupportedMailTypes(),
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/legacy/{id}/test', name: 'admin_email_templates_legacy_test')]
    public function legacyEmailTemplateTest(
        Request $request,
        Mail $emailTemplate,
    ): Response {
        $form = $this->createForm(MailParametersType::class, [
            'params' => [
                'skipObjectCheck' => true,
                'nested' => [
                    'param' => 'example',
                ],
            ],
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var ClickableInterface $syncSend */
                $syncSend = $form->get('submit');
                /** @var ClickableInterface $asyncSend */
                $asyncSend = $form->get('submitAsync');
                if ($syncSend->isClicked()) {
                    $this->mailerService->sendMail(
                        $this->getUser(),
                        $emailTemplate->getSlug(),
                        $form->getData()['params'],
                    );
                    $this->addFlash(
                        'success',
                        'Successfully sent email with template '
                            . $emailTemplate->getSlug(),
                    );
                } elseif ($asyncSend->isClicked()) {
                    $this->mailerService->sendMail(
                        $this->getUser(),
                        $emailTemplate->getSlug(),
                        $form->getData()['params'],
                        true,
                    );
                    // $this->addFlash('notice', 'To be implemented');
                    $this->addFlash(
                        'success',
                        'Successfully sent email asynchronously with template '
                            . $emailTemplate->getSlug(),
                    );
                } else {
                    $this->addFlash(
                        'warning',
                        'No test email sent. Unknown send option chosen.',
                    );
                }
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Failed to send email with template '
                        . $emailTemplate->getSlug()
                        . '. '
                        . $e->getMessage(),
                );
            }
        }
        return $this->render('admin/pages/email_templates/legacy/test_send.html.twig', [
            'emailTemplate' => $emailTemplate,
            'form' => $form->createView(),
        ]);
    }
}
