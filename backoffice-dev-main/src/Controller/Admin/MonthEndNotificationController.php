<?php

namespace App\Controller\Admin;

use App\Entity\Enum\PaymentType;
use App\Entity\Enum\TransferType;
use App\Entity\PaymentOrder;
use App\Entity\PaymentRequest;
use App\Entity\TransferOrder;
use App\Entity\TransferRequest;
use App\Entity\User;
use App\Message\OrderBatchNotify;
use App\Service\MonthEndEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/monthend')]
class MonthEndNotificationController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private MonthEndEmailService $monthEndEmailService,
    ) {}

    #[Route(
        '/payments/{id}/notifications',
        name: 'admin_monthend_payment_notifications',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function notificationManage(
        Request $request,
        PaymentOrder $paymentOrder,
        MessageBusInterface $bus,
    ): Response {
        $exitRoute = match ($paymentOrder->getPaymentType()) {
            PaymentType::Dividend->value => 'admin_monthend_dividend_manage',
            PaymentType::InvestmentExit->value,
            PaymentType::Divestment->value,
                => 'admin_monthend_divestment_manage',
            default => 'admin_payment_order_manage',
        };
        if (!in_array($paymentOrder->getPaymentType(), [
            PaymentType::Divestment->value,
            PaymentType::InvestmentExit->value,
            PaymentType::Dividend->value,
        ])) {
            $this->addFlash(
                'warning',
                "Notifications for payment type {$paymentOrder->getPaymentType()} not supported.",
            );
            return $this->redirectToRoute($exitRoute, ['id' => $paymentOrder->getId()]);
        }
        $form = $this
            ->createFormBuilder()
            ->add('submit', SubmitType::class, ['label' => 'Send All Notifications'])
            ->add('submitAsync', SubmitType::class, [
                'label' => 'Send All Notifications in Background',
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var ClickableInterface $asyncSend */
                $asyncSend = $form->get('submitAsync');
                if ($asyncSend->isClicked()) {
                    $this->logger->notice('Sending async');
                    // Submit sendALl as background job
                    /** @var UserInterface|User $currentUser  */
                    $currentUser = $this->getUser();
                    $bus->dispatch(new OrderBatchNotify(
                        orderFqcn: PaymentOrder::class,
                        orderId: $paymentOrder->getId(),
                        submittedByUserId: $currentUser->getId(),
                        autoContinue: true,
                    ));
                    $this->addFlash(
                        'success',
                        'Order notifications submitted as a background job. Refresh this page for progress updates. You will be notified on completion.',
                    );
                } else {
                    $this->logger->notice('Sending sync');
                    // Attempt to send all notifications
                    $this->monthEndEmailService->sendAllPaymentNotifications(
                        $paymentOrder,
                    );
                    $this->addFlash(
                        'success',
                        'All remaining email notifications successfully sent',
                    );
                }
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Unable to send all payment email notifications. '
                        . $e->getMessage(),
                );
                $this->logger->error('Unable send all payment email notifications.', [$e->getMessage()]);
            }
            $this->entityManager->flush();
        }
        return $this->render('admin/pages/monthend/payments/notification.html.twig', [
            'form' => $form->createView(),
            'paymentOrder' => $paymentOrder,
            'exitRoute' => $exitRoute,
        ]);
    }

    #[Route(
        '/payments/{id}/send-notification',
        name: 'admin_monthend_payment_notification_send',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function notificationSend(PaymentRequest $paymentRequest): Response
    {
        if (!in_array($paymentRequest->getPaymentOrder()->getPaymentType(), [
            PaymentType::Divestment->value,
            PaymentType::InvestmentExit->value,
            PaymentType::Dividend->value,
        ])) {
            $this->addFlash(
                'warning',
                "Notifications for payment type {$paymentRequest
                    ->getPaymentOrder()
                    ->getPaymentType()} not supported.",
            );
            return $this->redirectToRoute(
                'admin_payment_order_manage',
                ['id' => $paymentRequest->getPaymentOrder()->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }

        if (PaymentRequest::STATE_PAID == $paymentRequest->getStatus()) {
            if (!is_null($paymentRequest->getPayeeNotifiedAt())) {
                $this->logger->info('Resending payment notification', [
                    'paymentRequest' => $paymentRequest->getId(),
                ]);
            }
            try {
                // Attempt to send a notification
                $this->monthEndEmailService->sendOnePaymentNotification(
                    $paymentRequest,
                );
                $this->entityManager->flush();
                $this->addFlash(
                    'success',
                    'Payment notification email successfully sent to '
                        . $paymentRequest->getPayee()->getEmail(),
                );
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Unable to send payment email notification to '
                        . $paymentRequest->getPayee()->getEmail()
                        . '. '
                        . $e->getMessage(),
                );
                $this->logger->error('Unable to send payment email notification.', [
                    'paymentRequest' => $paymentRequest->getId(),
                    'error message' => $e->getMessage(),
                ]);
            }
        } else {
            $this->addFlash(
                'warning',
                'Payment must be paid before sending a notification',
            );
        }
        return $this->redirectToRoute(
            'admin_monthend_payment_notifications',
            ['id' => $paymentRequest->getPaymentOrder()->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route(
        '/settlements/{id}/notifications',
        name: 'admin_monthend_settlement_notifications',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function settlementNotificationManage(
        Request $request,
        TransferOrder $transferOrder,
        MessageBusInterface $bus,
    ): Response {
        if ($transferOrder->getTransferType() !== TransferType::InvestmentSettlement) {
            $this->addFlash(
                'warning',
                "Notifications for transfer type {$transferOrder->getTransferType()->value} not supported.",
            );
            return $this->redirectToRoute('admin_monthend_settlement_manage', ['id' => $transferOrder->getId()]);
        }
        $form = $this
            ->createFormBuilder()
            ->add('submit', SubmitType::class, ['label' => 'Send All Notifications'])
            ->add('submitAsync', SubmitType::class, [
                'label' => 'Send All Notifications in Background',
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var ClickableInterface $asyncSend */
                $asyncSend = $form->get('submitAsync');
                if ($asyncSend->isClicked()) {
                    // Submit sendALl as background job
                    /** @var UserInterface|User $currentUser  */
                    $currentUser = $this->getUser();
                    $bus->dispatch(new OrderBatchNotify(
                        orderFqcn: TransferOrder::class,
                        orderId: $transferOrder->getId(),
                        submittedByUserId: $currentUser->getId(),
                        autoContinue: true,
                    ));
                    $this->addFlash(
                        'success',
                        'Order notifications submitted as a background job. Refresh this page for progress updates. You will be notified on completion.',
                    );
                } else {
                    // Attempt to send all notifications
                    $this->monthEndEmailService->sendAllSettlementNotifications(
                        $transferOrder,
                    );
                    $this->addFlash(
                        'success',
                        'All remaining email notifications successfully sent',
                    );
                }
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Unable to send all settlement email notifications. '
                        . $e->getMessage(),
                );
                $this->logger->error('Unable send all settlement email notifications.', [$e->getMessage()]);
            }
            $this->entityManager->flush();
        }
        return $this->render('admin/pages/monthend/settlements/notification.html.twig', [
            'form' => $form->createView(),
            'transferOrder' => $transferOrder,
        ]);
    }

    #[Route(
        '/settlements/{id}/send-notification',
        name: 'admin_monthend_settlement_notification_send',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function settlementNotificationSend(TransferRequest $transferRequest): Response
    {
        if (
            $transferRequest->getTransferOrder()->getTransferType()
            !== TransferType::InvestmentSettlement
        ) {
            $this->addFlash(
                'warning',
                "Notifications for transfer type {$transferRequest
                    ->getTransferOrder()
                    ->getTransferType()->value} not supported.",
            );
            return $this->redirectToRoute('admin_monthend_settlement_manage', ['id' => $transferRequest
                ->getTransferOrder()
                ->getId()]);
        }

        if (TransferRequest::STATE_COMPLETE == $transferRequest->getStatus()) {
            if (!is_null($transferRequest->getUserNotifiedAt())) {
                $this->logger->info('Resending settlement notification', [
                    'transferRequest' => $transferRequest->getId(),
                ]);
            }
            try {
                // Attempt to send a notification
                $this->monthEndEmailService->sendOneSettlementNotification(
                    $transferRequest,
                );
                $this->entityManager->flush();
                $this->addFlash(
                    'success',
                    'Settlement notification email successfully sent to '
                        . $transferRequest
                            ->getShareTrade()
                            ->getBuyOrder()
                            ->getUser()
                            ->getEmail(),
                );
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Unable to send settlement email notification to '
                        . $transferRequest
                            ->getShareTrade()
                            ->getBuyOrder()
                            ->getUser()
                            ->getEmail()
                        . '. '
                        . $e->getMessage(),
                );
                $this->logger->error('Unable to send settlement email notification.', [
                    'transferRequest' => $transferRequest->getId(),
                    'error message' => $e->getMessage(),
                ]);
            }
        } else {
            $this->addFlash(
                'warning',
                'Settlement must be paid before sending a notification',
            );
        }
        return $this->redirectToRoute(
            'admin_monthend_settlement_notifications',
            ['id' => $transferRequest->getTransferOrder()->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }
}
