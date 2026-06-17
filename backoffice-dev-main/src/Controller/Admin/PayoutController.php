<?php

namespace App\Controller\Admin;

use App\Entity\Payout;
use App\Form\Type\PayoutType;
use App\Form\Type\QueryPayoutType;
use App\Repository\PayoutRepository;
use App\Service\Manager\PayoutManagerV2;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/payout')]
#[IsGranted('ROLE_ANALYST')]
class PayoutController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private PayoutRepository $payoutRepository,
        private PayoutManagerV2 $payoutManager,
    ) {}

    #[Route('', name: 'admin_payout_index', methods: ['GET'])]
    #[Route('/list', name: 'admin_payout_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $this->logger->info('List payouts');
        $form = $this->createForm(QueryPayoutType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->payoutRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/payouts/list.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_payout_edit', methods: ['GET', 'POST'])]
    public function editAction(Request $request, ?Payout $payout = null): Response
    {
        $readOnly = false;
        if (!$this->isGranted('CAN_UPDATE_INVESTMENT', $this->getUser())) {
            $readOnly = true;
        }

        $form = $this->createForm(PayoutType::class, $payout, [
            'read_only' => $readOnly,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->persist($payout);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_payout_index', [
                'id' => $payout->getId(),
            ]));
        }
        return $this->render('admin/pages/payouts/edit.html.twig', [
            'payout' => $payout,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/add', name: 'admin_payout_add', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_OPERATIONS')]
    public function addAction(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $payout = new Payout();
        $payout->setDueDate(new \DateTime('first day of this month'));
        $payout->setCreatedById($user->getId());
        $payout->setCurrency('GBP');
        $payout->setPayoutType(0);
        $form = $this->createForm(PayoutType::class, $payout, ['read_only' => false]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->info('Adding new retrospective payout');
            $em = $this->doctrine->getManager();
            $em->persist($payout);
            $em->flush();
            return $this->redirect($this->generateUrl('admin_payout_index', [
                'id' => $payout->getId(),
            ]));
        }
        return $this->render('admin/pages/payouts/edit.html.twig', [
            'payout' => $payout,
            'form' => $form->createView(),
        ]);
    }
}
