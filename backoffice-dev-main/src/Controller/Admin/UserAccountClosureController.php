<?php

namespace App\Controller\Admin;

use App\Entity\Enum\AccountCleanupAction;
use App\Entity\Enum\AccountRetentionLevel;
use App\Entity\Lifecycle\UserLifecycle;
use App\Entity\User;
use App\Form\Type\AccountClosureCleanupType;
use App\Form\Type\UsernameType;
use App\Repository\UserRepository;
use App\Service\AccountClosureService;
use App\Service\Manager\UserManager;
use App\Service\Manager\UserManagerV2;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/users')]
class UserAccountClosureController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $em,
        private AccountClosureService $accountClosureService,
        private UserManagerV2 $userManager,
        private UserManager $userManagerLegacy,
        private UserRepository $userRepository,
    ) {}

    #[Route(
        path: '/{id}/account-closure',
        name: 'admin_user_account_closure_overview',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function overview(#[MapEntity(id: 'id')] User $user): Response
    {
        /**
         * Show the available types of account closure
         * Shows checklist of data that needs deleting
         *   - Mangopay ID
         *   - Salesforce ID
         *   - Mailchimp subscriber lists (bizops will need to manually delete these)
         * And which are applicable to the current user
         * - Simple (no Mangopay account) - no data retention required
         * - Mid (has Mangopay account, no Mangopay transactions - implicitly means no wallet balance) - no data retention required
         * - Mid-complex - has Mangopay transactions - data retention required, and wallet may need to be emptied
         * - Complex - has investments - complete data retention - account can still be blocked
         */
        $restrictions =
            $this->accountClosureService->getAccountClosureRestrictions($user);
        $retentionLevel =
            $this->accountClosureService->getRetentionLevel($restrictions);
        // $this->logger->debug("Retention Level: {$retentionLevel->name}");
        return $this->render('admin/pages/users/account_closure/overview.html.twig', [
            'user' => $user,
            'restrictions' => $restrictions,
            'retentionLevel' => $retentionLevel,
            'mangopayUserStatus' =>
                $this->accountClosureService->getMangopayUserStatus($user),
            'salesforceContactExists' =>
                $this->accountClosureService->hasSalesforceContact($user),
        ]);
    }

    #[Route(
        path: '/{id}/account-closure/retention/{retentionLevel}',
        name: 'admin_user_account_closure_retention_level',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function retentionNone(
        Request $request,
        #[MapEntity(id: 'id')] User $user,
        AccountRetentionLevel $retentionLevel,
    ): Response {
        $restrictions =
            $this->accountClosureService->getAccountClosureRestrictions($user);
        $retentionLevelExpected =
            $this->accountClosureService->getRetentionLevel($restrictions);
        if (
            !in_array($retentionLevel, AccountRetentionLevel::minimalRetention())
            || !$this->accountClosureService->canCloseAccount($restrictions)
            || $retentionLevel !== $retentionLevelExpected
        ) {
            $this->addFlash(
                'notice',
                'Account closure cleanup only supported for retention levels: '
                    . json_encode(AccountRetentionLevel::minimalRetention()),
            );
            return $this->redirectToRoute('admin_user_account_closure_overview', ['id' => $user->getId()]);
        }
        $availableActions =
            AccountCleanupAction::actionsForRetentionLevel($retentionLevel);
        $defaultActions = $request->query->get('all', false)
            ? AccountCleanupAction::actionsForRetentionLevel($retentionLevel, true)
            : [];
        $form = $this->createForm(
            AccountClosureCleanupType::class,
            ['actions' => $defaultActions],
            ['actions' => $availableActions],
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $processedActions = $this->accountClosureService->cleanupData(
                $user,
                $form->getData()['actions'],
            );
            $this->em->flush();
            $this->logger->notice(
                "Account closure for user #{$user->getId()}",
                ['actions' => json_decode(json_encode($processedActions))],
            );
            $this->addFlash(
                'success',
                "Successfully cleaned up and deleted data for user #{$user->getId()}: "
                    . json_encode($processedActions),
            );
            return $this->redirectToRoute('admin_user_account_closure_overview', [
                'id' => $user->getId(),
            ]);
        }

        return $this->render('admin/pages/users/account_closure/cleanup.html.twig', [
            'form' => $form,
            'user' => $user,
            'restrictions' => $restrictions,
            'retentionLevel' => $retentionLevel,
            'mangopayUserStatus' =>
                $this->accountClosureService->getMangopayUserStatus($user),
            'salesforceContactExists' =>
                $this->accountClosureService->hasSalesforceContact($user),
            'availableActions' => $availableActions,
            'anonUsername' =>
                $this->accountClosureService->generateAnonymisedUsername($user),
        ]);
    }

    #[Route(
        path: '/{id}/account-closure/toggle-block',
        name: 'admin_user_account_closure_toggle_block',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function toggleBlock(#[MapEntity(id: 'id')] User $user): Response
    {
        $this->accountClosureService->toggleAccountBlock($user);
        $this->em->flush();
        return $this->redirectToRoute('admin_user_account_closure_overview', [
            'id' => $user->getId(),
        ]);
    }

    #[Route(
        path: '/{id}/update-username',
        name: 'admin_user_update_username',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function updateUsername(
        Request $request,
        #[MapEntity(id: 'id')] User $user,
    ): Response {
        // Special controller that provides an isolated place to update the username (typically not editable)
        $form = $this->createForm(UsernameType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (
                empty($this->em->getRepository(User::class)->findOneBy(['username' =>
                    $user->getUserIdentifier()]))
            ) {
                $this->em->flush();
                return $this->redirectToRoute('admin_user_account_closure_overview', ['id' => $user->getId()]);
            } else {
                $this->addFlash(
                    'error',
                    "Username already exists: {$user->getUserIdentifier()}",
                );
            }
        }
        return $this->render('admin/pages/users/update_username.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
