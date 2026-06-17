<?php

namespace App\Tests\Service\Manager;

use App\Entity\Asset;
use App\Entity\AssetDocuments;
use App\Service\Manager\DocumentManager;
use App\Test\Util\EntityIdTestUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DocumentManagerTest extends KernelTestCase
{
    private DocumentManager $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(DocumentManager::class);
    }

    public function testServiceExists(): void
    {
        $this->assertInstanceOf(DocumentManager::class, $this->service);
    }

    public function testGetRepositoryForDocType_Valid(): void
    {
        $output_asset = $this->service->getRepositoryForDocType('asset');
        $output_offering = $this->service->getRepositoryForDocType('offering');
        $output_user = $this->service->getRepositoryForDocType('user');
        $output_investment = $this->service->getRepositoryForDocType('investment');

        $this->assertNotEmpty($output_asset);
        $this->assertNotEmpty($output_offering);
        $this->assertNotEmpty($output_user);
        $this->assertNotEmpty($output_investment);
    }

    public function testGetRepositoryForDocType_Invalid(): void
    {
        $output = $this->service->getRepositoryForDocType('biscuits');
        $this->assertEquals([], $output);
    }

    public function testGetVisibilityForDocType_Private(): void
    {
        $output_user = $this->service->getVisibilityForDocType('user');
        $output_investment = $this->service->getVisibilityForDocType('investment');

        $this->assertEquals('private', $output_user);
        $this->assertEquals('private', $output_investment);
    }

    public function testGetVisibilityForDocType_Public(): void
    {
        $output_asset = $this->service->getVisibilityForDocType('asset');
        $output_offering = $this->service->getVisibilityForDocType('offering');
        $output_random = $this->service->getVisibilityForDocType('random');

        $this->assertEquals('public', $output_asset);
        $this->assertEquals('public', $output_offering);
        $this->assertEquals('public', $output_random);
    }

    public function testGeneratePrefixValidType(): void
    {
        /** @var Asset $asset */
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 1768);
        $assetDoc = new AssetDocuments();
        $asset->addDocument($assetDoc);

        $docs = $asset->getDocuments();
        $output = $this->service->generatePrefix($docs[0], 'asset');
        $this->assertMatchesRegularExpression('~asset/\d+/[a-z0-9]{16}_$~', $output);
    }

    public function testGeneratePrefix_InvalidType(): void
    {
        $output = $this->service->generatePrefix([], 'biscuit');
        $this->assertFalse($output);
    }

    public function testGetFileInfoList_Default(): void
    {
        $input = [
            [
                'path' => 'offering/1',
                'type' => 'dir',
            ],
            [
                'path' => 'offering/1/Test_PDF.pdf',
                'type' => 'file',
            ],
            [
                'path' => 'offering/2/Test_Excel.xlsx',
                'type' => 'file',
            ],
            [
                'path' => 'offering/30',
                'type' => 'dir',
            ],
            [
                'path' => 'offering/30/Test_PDF.pdf',
                'type' => 'file',
            ],
        ];
        $output = $this->service->getFileInfoList($input);
        $expected = [
            'offering/1/Test_PDF.pdf',
            'offering/2/Test_Excel.xlsx',
            'offering/30/Test_PDF.pdf',
        ];
        $this->assertEquals($expected, $output);
    }

    public function testGetFileInfoList_InfoType(): void
    {
        $input = [
            [
                'path' => 'offering/1',
                'type' => 'dir',
            ],
            [
                'path' => 'offering/1/Test_PDF.pdf',
                'extension' => 'pdf',
                'type' => 'file',
            ],
            [
                'path' => 'offering/2/Test_Excel.xlsx',
                'extension' => 'xlsx',
                'type' => 'file',
            ],
            [
                'path' => 'offering/30',
                'type' => 'dir',
            ],
            [
                'path' => 'offering/30/Test_PDF.pdf',
                'extension' => 'pdf',
                'type' => 'file',
            ],
        ];
        $output = $this->service->getFileInfoList($input, 'extension');
        $expected = ['pdf', 'xlsx', 'pdf'];
        $this->assertEquals($expected, $output);
    }

    public function testGetFileInfoList_IncludeDir(): void
    {
        $input = [
            [
                'path' => 'offering/1',
                'type' => 'dir',
            ],
            [
                'path' => 'offering/1/Test_PDF.pdf',
                'type' => 'file',
            ],
            [
                'path' => 'offering/2/Test_Excel.xlsx',
                'type' => 'file',
            ],
            [
                'path' => 'offering/30',
                'type' => 'dir',
            ],
            [
                'path' => 'offering/30/Test_PDF.pdf',
                'type' => 'file',
            ],
        ];
        $output = $this->service->getFileInfoList($input, 'path', true);
        $expected = [
            'offering/1',
            'offering/1/Test_PDF.pdf',
            'offering/2/Test_Excel.xlsx',
            'offering/30',
            'offering/30/Test_PDF.pdf',
        ];
        $this->assertEquals($expected, $output);
    }

    public function testAssetGeneratePublicCdnUrls(): void
    {
        // Create the asset object with at least a document with a url
        $doc = new \App\Entity\Document();
        $assetDoc = new \App\Entity\AssetDocuments();
        $asset = new \App\Entity\Asset();
        $doc->setDocumentUrl('fixtures/Test_PDF.pdf');
        $assetDoc->setDocument($doc);
        $asset->addDocument($assetDoc);

        // apply the processing function
        $asset = $this->service->generatePublicCdnUrls([$asset])[0];

        // check it correctly applies the cdn domain name
        $this->assertMatchesRegularExpression(
            '~cloudfront.net~',
            $asset->getDocuments()[0]->getDocument()->getDocumentUrl(),
        );
    }

    public function testOfferingGeneratePublicCdnUrls(): void
    {
        // Create the offering object with at least a document with a url
        $doc = new \App\Entity\Document();
        $offeringDoc = new \App\Entity\OfferingDocuments();
        $offering = new \App\Entity\Offering();
        $doc->setDocumentUrl('fixtures/Test_PDF.pdf');
        $offeringDoc->setDocument($doc);
        $offering->addDocument($offeringDoc);

        // apply the processing function
        $offering = $this->service->generatePublicCdnUrls([$offering])[0];

        // check it correctly applies the cdn domain name
        $this->assertMatchesRegularExpression(
            '~cloudfront.net~',
            $offering->getDocuments()[0]->getDocument()->getDocumentUrl(),
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('urlStringProvider')]
    public function testFormatDocumentUrl(string $expected, string $input): void
    {
        $actual = $this->service->formatDocumentUrl($input);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @psalm-return \Generator<string, array{0: 'invalid.cloudfront.net/offering/1/biscuit.jpg'|'offering/1/biscuit with some spacejam.jpg'|'offering/1/biscuit.jpg', 1: string}, mixed, void>
     */
    public static function urlStringProvider(): \Generator
    {
        yield 'https protocol' => [
            'offering/1/biscuit.jpg',
            'https://d2yvvobu8dk8og.cloudfront.net/offering/1/biscuit.jpg',
        ];
        yield 'http protocol' => [
            'offering/1/biscuit.jpg',
            'http://d2yvvobu8dk8og.cloudfront.net/offering/1/biscuit.jpg',
        ];
        yield 'no protocol' => [
            'offering/1/biscuit.jpg',
            'd2yvvobu8dk8og.cloudfront.net/offering/1/biscuit.jpg',
        ];
        yield 'spaces in file name' => [
            'offering/1/biscuit with some spacejam.jpg',
            'd2yvvobu8dk8og.cloudfront.net/offering/1/biscuit with some spacejam.jpg',
        ];
        yield 'no action required' => [
            'offering/1/biscuit.jpg',
            'offering/1/biscuit.jpg',
        ];
        yield 'non matching cdn url' => [
            'invalid.cloudfront.net/offering/1/biscuit.jpg',
            'invalid.cloudfront.net/offering/1/biscuit.jpg',
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('docUrlProvider')]
    public function testGetPublicCdnUrl(?string $expected, ?string $input): void
    {
        $actual = $this->service->getPublicCdnUrl($input);
        $this->assertSame($expected, $actual);
    }

    public static function docUrlProvider(): \Generator
    {
        yield 'has url' => [
            'https://d2yvvobu8dk8og.cloudfront.net/asset/1/biscuit.jpg',
            'asset/1/biscuit.jpg',
        ];
        yield 'empty url' => [
            null,
            '',
        ];
        yield 'null url' => [
            null,
            null,
        ];
    }
}
