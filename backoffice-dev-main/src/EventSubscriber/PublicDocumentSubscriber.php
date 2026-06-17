<?php

namespace App\EventSubscriber;

use App\Entity\AssetDocuments;
use App\Entity\InvestmentDocuments;
use App\Entity\OfferingDocuments;
use JMS\Serializer\EventDispatcher\Events as SerializerEvents;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use Psr\Log\LoggerInterface;

class PublicDocumentSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private string $cdnDomainName,
        private LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            [
                'event' => SerializerEvents::PRE_SERIALIZE,
                'method' => 'onPreSerialize',
                'class' => AssetDocuments::class,
            ],
            [
                'event' => SerializerEvents::PRE_SERIALIZE,
                'method' => 'onPreSerialize',
                'class' => OfferingDocuments::class,
            ],
            [
                'event' => SerializerEvents::PRE_SERIALIZE,
                'method' => 'onPreSerialize',
                'class' => InvestmentDocuments::class,
            ],
        ];
    }

    public function onPreSerialize(PreSerializeEvent $event)
    {
        $this->logger->debug('Generating CDN url for public document');

        $object = $event->getObject();
        $document = $object->getDocument();
        $url = $document->getDocumentUrl();

        if (!empty($url)) {
            $cdnUrl = 'https://' . $this->cdnDomainName . '/' . $url;
            $document->setDocumentUrl($cdnUrl);
            $object->setDocument($document);
        }
    }
}
