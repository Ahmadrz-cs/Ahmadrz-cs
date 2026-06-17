<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Service\Manager\UserSecurityManager;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/profile')]
class ProfileController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private UserSecurityManager $userSecurityManager,
        private TotpAuthenticatorInterface $totpAuthenticatorInterface,
        private ?string $kmsKeyId,
    ) {}

    #[Route(path: '/', name: 'admin_profile_index')]
    public function indexAction()
    {
        $this->logger->debug('=====IN profile indexAction=====');

        $this->checkKmsKeySet();
        $user = $this->getUser();
        $mfaConfig = $this->userSecurityManager->getUserMfaConfig($user);
        return $this->render('admin/pages/profile/index.html.twig', [
            'mfaConfig' => $mfaConfig,
        ]);
    }

    #[Route(path: '/mfa', name: 'admin_profile_mfa_manage')]
    public function mfaManageAction()
    {
        $this->logger->debug('=====IN profile mfaManageAction=====');
        $this->checkKmsKeySet();
        /** @var User $user */
        $user = $this->getUser();
        $mfaConfig = $this->userSecurityManager->getUserMfaConfig($user);
        $this->logger->notice('Mfa config: ' . json_encode($mfaConfig));
        return $this->render('admin/pages/profile/mfa_manage.html.twig', [
            'mfaConfig' => $mfaConfig,
            'mfaPreference' => $user->getSecurity()->getMfaPreference(),
        ]);
    }

    #[Route(path: '/mfa/setup/totp', name: 'admin_profile_mfa_setup_totp')]
    public function mfaSetupTotpAction(Request $request)
    {
        $this->logger->debug('=====IN profile mfaSetupTotpAction=====');

        $this->checkKmsKeySet();
        /** @var User $user */
        $user = $this->getUser();
        $mfaTotp = $user->getSecurity()->getMfaTotp();
        if ($mfaTotp) {
            return $this->redirectToRoute('admin_profile_mfa_manage');
        }

        $mfaType = $user->getSecurity()->getMfaTotp();
        $mfaKey = $user->getSecurity()->getTotpKey();

        $form = $this
            ->createFormBuilder([])
            ->add('code1', TextType::class, [
                'label' => 'Authentication Code 1',
            ])
            ->add('code2', TextType::class, [
                'label' => 'Authentication Code 2',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enable Two-Factor-Authentication',
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->debug('Confirming setup codes');
            $data = $form->getData();
            $codeCheckValid = $this->userSecurityManager->checkTotpCodes($user, [
                $data['code1'],
                $data['code2'],
            ]);
            $codeCheckDiff = $data['code1'] != $data['code2'];
            if ($codeCheckValid && $codeCheckDiff) {
                $this->userSecurityManager->enableMfa($user, 'totp');
                $this->addFlash('success', 'Two-Factor-Authentication is now enabled');
                return $this->redirectToRoute('admin_profile_mfa_manage');
            } elseif ($codeCheckValid && !$codeCheckDiff) {
                $this->addFlash(
                    'warning',
                    'Codes were valid but identical. Please attempt setup again and provide 2 different codes.',
                );
            } else {
                $this->addFlash('error', 'Codes invalid. Please attempt setup again.');
            }
            return $this->redirectToRoute('admin_profile_mfa_setup_totp');
        }

        if (empty($mfaType) || empty($mfaKey)) {
            $mfaKey = $this->userSecurityManager->generateTotpKey($user);
        }

        $qrCodeContent = $this->totpAuthenticatorInterface->getQRContent($user);

        return $this->render('admin/pages/profile/mfa_setup.html.twig', [
            'form' => $form->createView(),
            'mfamessage' => $qrCodeContent,
            'mfaKey' => $mfaKey,
        ]);
    }

    #[Route(path: '/mfa/setup/email', name: 'admin_profile_mfa_setup_email')]
    public function mfaSetupEmailAction()
    {
        $this->logger->debug('=====IN profile mfaSetupEmailAction=====');

        $user = $this->getUser();
        if ($this->userSecurityManager->enableMfa($user, 'email')) {
            $this->addFlash('success', 'Two-Factor-Authentication is now enabled');
        } else {
            $this->addFlash(
                'error',
                'Could not enable email 2FA. Please contact admin.',
            );
        }

        return $this->redirectToRoute('admin_profile_mfa_manage');
    }

    #[Route(path: '/mfa/disable/{factor}', name: 'admin_profile_mfa_disable')]
    public function mfaDisableAction(string $factor)
    {
        $user = $this->getUser();
        $this->logger->info('Turning off 2fa for user: ' . $user->getUserIdentifier());

        $disableRsp = $this->userSecurityManager->disableMfa($user, $factor);
        if (!$disableRsp) {
            $this->addFlash('warning', '2FA factor not supported. Nothing to disable!');
            return $this->redirectToRoute('admin_profile_mfa_manage');
        }
        $this->addFlash('success', 'Two-Factor-Authentication successfully disabled.');
        return $this->redirectToRoute('admin_profile_mfa_manage');
    }

    #[Route(path: '/mfa/preference/{factor}', name: 'admin_profile_mfa_preference')]
    public function mfaPreferenceAction(string $factor)
    {
        $this->logger->debug('=====IN profile mfaPreferenceAction=====');

        $user = $this->getUser();
        $prefRsp = $this->userSecurityManager->setMfaPreference($user, $factor);

        return $this->redirectToRoute('admin_profile_mfa_manage');
    }

    /**
     * @param Request $request
     */
    #[Route(path: '/mfa/check', name: 'admin_profile_mfa_check')]
    public function mfaCheckAction(Request $request)
    {
        $this->logger->debug('=====IN mfaCheckAction=====');

        /** @var User $user */
        $user = $this->getUser();
        $mfaTotp = $user->getSecurity()->getMfaTotp();
        if (!$mfaTotp) {
            $this->addFlash('warning', '2FA not enabled. Nothing to check!');
            return $this->redirectToRoute('admin_profile_mfa_manage');
        }
        $form = $this
            ->createFormBuilder([])
            ->add('code1', TextType::class, [
                'label' => 'Authentication Code',
            ])
            // ->add('code2', TextType::class)
            ->add('submit', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->debug('Checking 2fa codes');
            $data = $form->getData();
            try {
                $codeCheckValid = $this->userSecurityManager->checkTotpCodes($user, [
                    $data['code1'],
                ]);
                if ($codeCheckValid) {
                    $this->addFlash('success', 'Code valid');
                } else {
                    $this->addFlash('error', 'Code not valid');
                }
            } catch (\Exception $e) {
                $this->logger->error('Unable to check code: ' . $e->getMessage());
                $this->addFlash('error', 'Error checking code: ' . $e->getMessage());
            }
        }

        return $this->render('admin/pages/profile/mfa_check.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function checkKmsKeySet()
    {
        if (empty($this->kmsKeyId)) {
            $this->addFlash(
                'error',
                '2FA encryption not working. No key configured. Please contact admin.',
            );
            return false;
        }
        return true;
    }
}
