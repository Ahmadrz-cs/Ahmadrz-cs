<?php

namespace App\Service;

use App\Entity\Asset;
use App\Entity\Communication;
use App\Entity\Enum\EmailTemplate;
use App\Entity\Investment;
use App\Entity\Mail;
use App\Entity\User;
use App\Entity\UserMail;
use App\Exception\InsufficientEmailParamsException;
use App\Exception\InvalidEmailTypeException;
use App\Repository\CommunicationRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\User\UserInterface;

class MailerService implements AuthCodeMailerInterface
{
    public const TYPE_INVESTMENT_NEW = 'investment.new';
    public const TYPE_INVESTMENT_SETTLED = 'investment.settled';
    public const TYPE_OFFERING_NEW = 'offering.new';
    public const TYPE_RELIST_OFFERING_NEW_admin = 'relist.offering.new.admin';
    public const TYPE_RELIST_OFFERING_NEW = 'relist.offering.new';
    public const TYPE_FUNDING_GOAL_FIFTY_PERCENT = 'fundinggoal.fiftypercent';
    public const TYPE_USER_REGISTRATION = 'user.registration';
    public const TYPE_USER_REGISTRATION_ADMIN = 'user.registration.admin';
    public const TYPE_USER_PASSWORD_FORGOT = 'user.password.forgot';
    public const TYPE_USER_PASSWORD_FORGOT_ADMIN = 'user.password.forgot.admin';
    public const TYPE_USER_PASSWORD_CHANGE_CONF = 'password.reset.confirmation';
    public const TYPE_CONTEGO_RESPONSE_GREEN = 'contego.response.green';
    public const TYPE_CONTEGO_RESPONSE_RED = 'contego.response.red';
    public const TYPE_CONTEGO_RESPONSE_AMBER = 'contego.response.amber';
    public const TYPE_FUNDING_GOAL_HUNDRED_PERCENT = 'fundinggoal.hundredpercent';
    public const TYPE_OFFERING_CANCELLED = 'offering.cancelled';
    public const TYPE_TRANSACTION_CREATED = 'transaction.created';
    public const TYPE_TRANSACTION_FAILED = 'transaction.failed';
    public const TYPE_TRANSACTION_PAID = 'transaction.paid';
    public const TYPE_TRANSACTION_CANCELLED = 'transaction.cancelled';
    public const TYPE_ASSET_NEW = 'asset.new';
    public const TYPE_USER_APPROVED = 'user.approved';
    public const TYPE_EMAIL_TOP_YIELDER = 'yielder.top';
    public const TYPE_MANGOPAY_PAYIN_BANKTRANSFER = 'mangopay.payin_banktransfer';
    public const TYPE_USER_REJECT_GDPR = 'user.gdpr_reject.admin';
    public const TYPE_OB_COMPLETE = 'user.onboarding.complete';
    public const TYPE_OB_COMPLETE_ADMIN = 'user.onboarding.complete.admin';
    public const TYPE_OB_RESUBMIT = 'user.onboarding.resubmit';
    public const TYPE_OB_RESUBMIT_ADMIN = 'user.onboarding.resubmit.admin';
    public const TYPE_OB_CONTACT = 'user.onboarding.contact';
    public const TYPE_OB_CONTACT_ADMIN = 'user.onboarding.contact.admin';
    public const TYPE_OB_QUESTIONNAIRE_RESET = 'user.onboarding.questionnaire_reset';
    public const TYPE_DIRECT_DEBIT_SETUP = 'mangopay.setup.direct_debit';
    public const TYPE_DIRECT_DEBIT_PAYMENT_PROCESSED = 'mangopay.direct_debit.payin';
    public const TYPE_DIRECT_DEBIT_AMOUNT_CHANGED = 'direct_debit.amount.changed';
    public const TYPE_DIRECT_DEBIT_CANCELLATION = 'direct_debit.cancellation';
    public const TYPE_USER_MFA_EMAIL_CODE = 'user.mfa.email_code';
    public const TYPE_DIVIDEND_PAYMENT = 'dividend.payment_confirmation';
    public const TYPE_DIVESTMENT_PAYMENT = 'divestment.payment_confirmation';
    public const TYPE_VIP_CONFIRMATION = 'user.vip_confirmation';
    public const TYPE_VIP_APPLICATION = 'user.vip_application';

    /** @var EntityRepository $mailRepository */
    private $mailRepository;

    /** @var Mail $mail */
    protected $mail;

    public function __construct(
        private MailerInterface $mailer,
        private \Twig\Environment $twig,
        private EntityManagerInterface $entityManager,
        private string $mailFromEmail,
        private string $mailFromName,
        private string $adminEmailAddr,
        private bool $useNewTemplate,
        private CommunicationRepository $communicationRepository,
        private LoggerInterface $logger,
    ) {
        $this->mailRepository = $entityManager->getRepository(Mail::class);
    }

    /**
     * Sends an email to a User
     *
     * @throws \Exception
     */
    public function sendMail(
        User $user,
        string $type,
        array $params = [],
        bool $async = false,
    ): int {
        $this->loadMail($type);

        if (!isset($params['user'])) {
            $params['user'] = $user;
        }

        $message = $this->createMail($this->mail, $params);
        $message->to(new Address($user->getEmail(), $user->getFullname()));

        try {
            if ($async) {
                $message->getHeaders()->addTextHeader('X-Bus-Transport', 'async');
            }
            $this->mailer->send($message);
        } catch (\Exception $e) {
            // Log entry for failed mail and then rethrow the exception
            $this->communicationEntry($user, $message, 0);
            $this->logger->error('Sending email FAILED  ...' . $e->getMessage());
            throw $e;
        }

        //Log entry for mail
        $this->communicationEntry($user, $message, 1);

        // temp fix for password reset emails to admin
        switch ($type) {
            case 'user.registration':
                $type = 'user.registration.admin';
                break;

            case 'user.password.forgot':
                $type = 'user.password.forgot.admin';
                break;

            case 'relist.offering.new':
                $type = 'relist.offering.new.admin';
                break;
        }

        //Sending mail for admin user
        $this->adminMailEntry($user, $type, $params, $message);
        // return $sendMailStatus;
        return 1;
    }

    /**
     * Send abritrary text only emails to a single user
     *
     * - Does not use a template
     */
    public function sendTextEmail(
        UserInterface|User|string $recipient,
        string $emailSubject,
        string $messageBody,
        bool $async = false,
    ) {
        $message = $this->createCustomMail($emailSubject, $messageBody);
        if (is_string($recipient)) {
            $message->to(new Address($recipient));
        } else {
            $message->to(
                new Address($recipient->getEmail(), $recipient->getFirstname()),
            );
        }
        if ($async) {
            $message->getHeaders()->addTextHeader('X-Bus-Transport', 'async');
        }
        $this->mailer->send($message);
    }

    /**
     * Similar to sendTextEmail, but can specify a Twig template
     *
     * This is a prototype related to https://gitlab.com/yielders2/backoffice-dev/-/issues/2199
     * and is subject to changes or removal in the future
     */
    public function sendTemplatedEmail(
        UserInterface|User|string $recipient,
        string $emailSubject,
        string $messageBody = '',
        array $context = [],
        EmailTemplate $template = EmailTemplate::Basic,
        bool $async = false,
    ) {
        $email = new TemplatedEmail()
            ->subject($emailSubject)
            ->from(new Address($this->mailFromEmail, $this->mailFromName))
            ->htmlTemplate("mail/{$template->value}.html.twig")
            ->textTemplate("mail/txt/{$template->value}.txt.twig");

        // context contains the variables we're passing to the Twig template
        $context = array_merge($context, [
            'content' => $messageBody,
        ]);
        if (is_string($recipient)) {
            $email->to(new Address($recipient));
        } else {
            $email->to(new Address($recipient->getEmail(), $recipient->getFirstname()));
        }
        if ($async) {
            $email->getHeaders()->addTextHeader('X-Bus-Transport', 'async');
        }
        $email->context($context);
        try {
            $this->mailer->send($email);
            $sendStatus = true;
        } catch (\Throwable $th) {
            $this->logger->error('Unable to send templated email', ['error' =>
                $th->getMessage()]);
            $sendStatus = false;
        } finally {
            /**
             * Log comms if
             * - Email is not an internal comms email - indicated by use of the Basic email template
             * - Recipient is a user (to associate a log with)
             */
            if ($template !== EmailTemplate::Basic && $recipient instanceof User) {
                $this->logger->debug('Recording comm entry', ['user' => $recipient->getId()]);
                $this->templatedCommunicationEntry($recipient, $email, $sendStatus);
            }
        }
    }

    public function sendAuthCode(TwoFactorInterface|User $user): void
    {
        if ($this->useNewTemplate) {
            $this->logger->debug('New email template');
            $this->sendTemplatedEmail(
                recipient: $user,
                emailSubject: 'Yielders Login Verification Code',
                context: [
                    'recipient' => $user->getFirstname(),
                    'emailAuthCode' => $user->getEmailAuthCode(),
                ],
                template: EmailTemplate::LoginAuthCode,
            );
        } else {
            $this->logger->debug('Old email template');
            $this->sendMail($user, MailerService::TYPE_USER_MFA_EMAIL_CODE, [
                'user' => $user,
            ]);
        }
    }

    /**
     * Log the email as a communications record
     */
    public function communicationEntry(
        User $user,
        Email $message,
        int|bool $sendStatus,
    ): void {
        $communication = new Communication();
        $communication->setRecipient($user);

        $communication->setContent($message->getTextBody());
        $communication->setSubject(trim($message->getSubject()));
        $communication->setStatus($sendStatus);

        $this->entityManager->persist($communication);
        $this->entityManager->flush();
    }

    public function templatedCommunicationEntry(
        User $user,
        TemplatedEmail $email,
        bool $sendStatus,
    ): void {
        $communication = new Communication();
        $communication->setRecipient($user);

        $communication->setContent($email->getContext()['content']);
        $communication->setSubject(trim($email->getSubject()));
        $communication->setStatus($sendStatus);

        $this->entityManager->persist($communication);
        $this->entityManager->flush();
    }

    /**
     * Manages the sending of the admin emails
     */
    public function adminMailEntry(
        User $user,
        string $type,
        array $params,
        ?Email $originalMessage,
    ): void {
        $this->loadMail($type);

        if ($this->mail->getSendAdmin() == true) {
            $role = 'ROLE_SUPER_ADMIN';
            $adminUsers = $this->entityManager
                ->getRepository(User::class)
                ->findByRole($role);
            // if ( ! isset( $params['user'] ) )
            // {
            //     $params['user'] = $adminUser;
            // }

            $message = $this->createMail($this->mail, $params, true);
            $message->to($this->adminEmailAddr);

            $this->mailer->send($message);
            unset($message);

            //Log entry for admin mail
            if ($originalMessage == null) {
                $originalMessage = $this->createMail($this->mail, $params, false);
            }
            $originalMessage->subject('[Admin] ' . $originalMessage->getSubject());
            $this->communicationEntry($adminUsers[0], $originalMessage, 1);
        }
    }

    /**
     * @deprecated Not currently used, marking for removal
     */
    public function adminCustomMailEntry(
        string $emailSubject,
        string $messageBody,
    ): void {
        $message = $this->createCustomMail($emailSubject, $messageBody);
        $role = 'ROLE_SUPER_ADMIN';
        $adminUsers = $this->entityManager
            ->getRepository(User::class)
            ->findByRole($role);

        $message->to($this->adminEmailAddr);
        $this->mailer->send($message);

        //Log entry for admin mail
        $this->communicationEntry($adminUsers[0], $message, 1);
    }

    public function getSupportedMailTypes(): array
    {
        // Back compat equivalent to using enums
        $reflectionClass = new \ReflectionClass($this);
        $constants = $reflectionClass->getConstants();
        return array_filter(
            $constants,
            fn($k) => 'TYPE_' == substr($k, 0, 5),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * Loads the mail instance into the $mail property
     *
     * @throws InvalidEmailTypeException if the slug is not found
     */
    private function loadMail(string $type): void
    {
        $mail = $this->mailRepository->findOneBy(['slug' => $type]);
        if (!$mail) {
            throw new InvalidEmailTypeException($type);
        }
        $this->mail = $mail;
    }

    private function getMailHtml(Mail $mail, array $params = []): string
    {
        $template = $this->twig->createTemplate($mail->getBody());
        return $template->render($params);
    }

    private function createMail(
        Mail $mail,
        array $params = [],
        int|bool $isAdmin = 0,
    ): Email {
        $mailBody = $this->getMailHtml($mail, $params);

        $template = $this->useNewTemplate
            ? EmailTemplate::Compatibility->value
            : 'default';

        $newmail = new Email()
            ->subject($mail->getSubject())
            ->from(new Address($this->mailFromEmail, $this->mailFromName))
            ->text(nl2br(strip_tags($mailBody)))
            ->html($this->twig->render("mail/{$template}.html.twig", [
                'body' => $mailBody,
            ]));

        //special case if admin then preface subject with admin to subject
        if ($isAdmin == 1) {
            $newmail->subject('[Admin] ' . $newmail->getSubject());
        }
        return $newmail;
    }

    private function createCustomMail(string $emailSubject, string $messageBody): Email
    {
        return new Email()
            ->subject($emailSubject)
            ->from(new Address($this->mailFromEmail, $this->mailFromName))
            ->text(nl2br(strip_tags($messageBody)))// ->html(
        //     $this->twig->render(
        //         'mail/default.html.twig',
        //         [
        //             'body' => $messageBody
        //         ]
        //     ),
        // )
        ;
    }
}
