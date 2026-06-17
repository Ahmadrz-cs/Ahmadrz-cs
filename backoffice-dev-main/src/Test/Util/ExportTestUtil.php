<?php

namespace App\Test\Util;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Form;

final class ExportTestUtil
{
    public static function downloadCsvToArray(
        KernelBrowser $client,
        string $path,
        ?Form $form = null,
    ): array {
        /**
         * Capture the export streamed response into the output buffer
         *
         * Workaround by converting into a stream wrapper
         * https://stackoverflow.com/a/44448941
         * https://www.php.net/manual/en/wrappers.php.php
         * Configure memory limits as appropriate (for performance)
         */
        ob_start();

        if (isset($form)) {
            $client->submit($form);
        } else {
            $client->request('GET', $path);
        }

        ob_get_clean();
        $csvString = $client->getInternalResponse()->getContent();

        $filePointer = fopen('php://temp', 'r+');
        fputs($filePointer, $csvString);
        rewind($filePointer);

        $csvAsArray = [];
        while (($row = fgetcsv($filePointer)) !== false) {
            $csvAsArray[] = $row;
        }
        return $csvAsArray;
    }
}
