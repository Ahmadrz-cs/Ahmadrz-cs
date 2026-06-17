<?php

namespace App\Service\Manager;

use App\Entity\DirectDebit;
use App\Entity\User;
use App\Service\MailerService;
use App\Service\Manager\BaseManager;

class DirectDebitManager extends BaseManager
{
    protected $entityClass = DirectDebit::class;

    public function sendDirectDebitSetupMail(
        User $user,
        $confirmationURL,
        $nextPaymentDate,
        $amount,
    ) {
        try {
            $sent = $this->getEmailService()->sendMail(
                $user,
                MailerService::TYPE_DIRECT_DEBIT_SETUP,
                [
                    'user' => $user,
                    'confirmationURL' => $confirmationURL,
                    'nextPaymentDate' => $nextPaymentDate,
                    'amount' => $amount,
                ],
            );

            if ($sent == 1) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function sendDirectDebitPaymentProcessedMail(User $user, $amount)
    {
        try {
            $sent = $this->getEmailService()->sendMail(
                $user,
                MailerService::TYPE_DIRECT_DEBIT_PAYMENT_PROCESSED,
                [
                    'user' => $user,
                    'amount' => $amount,
                ],
            );

            if ($sent == 1) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function sendDirectDebitAmountChangeEmail(User $user, $amount)
    {
        try {
            $sent = $this->getEmailService()->sendMail(
                $user,
                MailerService::TYPE_DIRECT_DEBIT_AMOUNT_CHANGED,
                [
                    'user' => $user,
                    'amount' => $amount,
                ],
            );

            if ($sent == 1) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function sendDirectDebitCancellationEmail(User $user)
    {
        try {
            $sent = $this->getEmailService()->sendMail(
                $user,
                MailerService::TYPE_DIRECT_DEBIT_CANCELLATION,
                [
                    'user' => $user,
                ],
            );

            if ($sent == 1) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            return false;
        }
    }
}
