<?php

namespace App\MessageHandler;

use App\Message\DebugLog;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
class DebugLogHandler
{
    private const string ERROR_CHECK_STRING = 'fail me';

    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function __invoke(DebugLog $message): void
    {
        if ($message->getContent() === self::ERROR_CHECK_STRING) {
            $this->logger->debug(
                'Received special error-check string. Will throw exception.',
            );
            throw new UnrecoverableMessageHandlingException(
                'Special error-check string submitted. Crashing out instead.',
            );
        }
        $this->logger->debug($message->getContent());
    }
}
