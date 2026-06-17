<?php

namespace App\Service;

use App\Entity\Enum\EmailTemplate;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class NotificationService
{
    public function __construct(
        private LoggerInterface $logger,
        private MailerService $mailerService,
        private string $engineeringAddress,
    ) {}

    public function notifyUserByEmail(
        User|UserInterface|string|null $recipient,
        string $subject,
        string $content,
        array $context = [],
        bool $isUserStaff = false,
    ) {
        if ($recipient === null) {
            $recipient = $this->engineeringAddress;
        }
        if (empty($context)) {
            $context = ['title' => $subject];
        }
        $template = $isUserStaff ? EmailTemplate::Basic : EmailTemplate::BasicCustomer;
        if ($recipient instanceof User) {
            // Pass the name to the template for the greeting
            $context['recipient'] = $recipient->getFirstname();
            $this->logger->debug("Sending notification to user #{$recipient->getId()}");
        } else {
            $this->logger->debug("Sending notification to user: {$recipient}");
        }
        try {
            $this->mailerService->sendTemplatedEmail(
                recipient: $recipient,
                emailSubject: $subject,
                messageBody: $content,
                context: $context,
                template: $template,
                async: false,
            );
        } catch (\Throwable $th) {
            $this->logger->error(
                'Unable to send email notification. ' . $th->getMessage(),
            );
        }
    }
}
