<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class MangopayReportDownloadService
{
    public function __construct(
        private LoggerInterface $logger,
        private ?array $allowedHosts,
    ) {
        if (is_null($allowedHosts)) {
            $this->allowedHosts = [];
        }
    }

    /**
     * @throws \RuntimeException if url is not from allowed hosts or issue downloading from url
     */
    public function downloadFromUrlToString(string $url): string
    {
        if ($this->validateUrl($url)) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_FAILONERROR, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            // Enabling CURLOPT_RETURNTRANSFER will return the response as a string
            // It will also return the boolean true if the response was 0 bytes
            // Which in this case implies a failed download (something went wrong)
            // Strict type checking comparator must be used here, === not just ==
            if ($response === true) {
                throw new \RuntimeException(
                    'Response when downloading from url was empty',
                );
            }
            // Enabling CURLOPT_FAILONERROR will cause response state codes >=400 to be considered failed
            // Failed responses have the boolean value false which we'll throw as an exception to handle upstream
            if ($response === false && curl_error($ch)) {
                $errorMessage = curl_error($ch);
                throw new \RuntimeException(
                    "Error downloading from url: {$errorMessage}",
                );
            }
            return $response;
        } else {
            throw new \RuntimeException(
                'Unsupported CSV source. Only the following hosts are supported: '
                    . join(', ', $this->allowedHosts),
            );
        }
    }

    private function validateUrl(string $url): bool
    {
        $parsedUrl = parse_url($url);
        if (in_array($parsedUrl['host'], $this->allowedHosts)) {
            return true;
        }
        return false;
    }
}
