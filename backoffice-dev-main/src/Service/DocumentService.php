<?php

namespace App\Service;

use League\Flysystem\DirectoryListing;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

/**
 * This service is just a wrapper around the flysystem
 * It centralises where the different file systems are managed
 * And directs flysystem API calls to the right file system
 */
class DocumentService
{
    public function __construct(
        private FilesystemOperator $publicFileSystem,
        private FilesystemOperator $privateFileSystem,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param string $filepath
     * @param string $filestring
     * @param string $visibility
     */
    public function put($filepath, $filestring, $visibility = 'private'): void
    {
        $this->logger->info('Uploading file: ' . $filepath);
        $this->getFileSystem($visibility)->write($filepath, $filestring);
    }

    /**
     * @param string $filepath
     * @param string $visibility
     */
    public function read($filepath, $visibility = 'private'): string
    {
        $this->logger->info('Downloading from: ' . $filepath);
        $response = $this->getFileSystem($visibility)->read($filepath);
        return $response;
    }

    /**
     * @param string $filepath
     * @param string $visibility
     */
    public function delete($filepath, $visibility = 'private'): void
    {
        $this->logger->info('Deleting: ' . $filepath);
        $this->getFileSystem($visibility)->delete($filepath);
    }

    /**
     * @param string $filepath
     * @param string $visibility
     */
    public function has($filepath, $visibility = 'private'): bool
    {
        $this->logger->info('Checking: ' . $filepath . ' exists');
        $response = $this->getFileSystem($visibility)->fileExists($filepath);
        return $response;
    }

    /**
     * @param string $filepath
     * @param string $visibility
     */
    public function fileSize($filepath, $visibility = 'private'): int
    {
        $this->logger->info('Checking file size: ' . $filepath);
        $response = $this->getFileSystem($visibility)->fileSize($filepath);
        return $response;
    }

    /**
     * @param string $filepath
     * @param string $visibility
     */
    public function listContents($filepath, $visibility = 'private'): DirectoryListing
    {
        $this->logger->info('Listing files in: ' . $filepath);
        $response = $this->getFileSystem($visibility)->listContents($filepath, true);
        return $response;
    }

    /**
     * @param string $visibility
     */
    protected function getFileSystem($visibility = 'private'): FilesystemOperator
    {
        if ($visibility == 'private') {
            return $this->privateFileSystem;
        } else {
            return $this->publicFileSystem;
        }
    }
}
