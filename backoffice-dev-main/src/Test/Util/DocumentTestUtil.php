<?php

namespace App\Test\Util;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Field\FileFormField;

final class DocumentTestUtil
{
    public static function addTempTestDoc(
        KernelBrowser $client,
        string $formPagePath,
        string $filePath,
        string $tag = 'tempFileForDeletion',
        bool $asCollection = true,
    ): void {
        $crawler = $client->request('GET', $formPagePath);
        $form = $crawler->filter('form')->form();
        if ($asCollection) {
            $form[$form->getName() . '[document][0][tag]'] = $tag;
        } else {
            $form[$form->getName() . '[document][tag]'] = $tag;
        }

        /**
         * https://symfony.com/doc/current/components/dom_crawler.html#forms
         * Use get() to retrieve the FileFormField to allow intellisense to work
         */
        /** @var FileFormField fileField */
        if ($asCollection) {
            $fileField = $form->get($form->getName() . '[document][0][file]');
        } else {
            $fileField = $form->get($form->getName() . '[document][file]');
        }

        $fileField->upload($filePath);
        $client->submit($form);
    }
}
