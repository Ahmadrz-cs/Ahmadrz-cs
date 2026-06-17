<?php

/**
 * Created by PhpStorm.
 */

namespace App\Tests\Entity;

use App\Entity\Asset;
use App\Entity\AssetDocuments;
use App\Entity\Document;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class AssetDocumentsTest
 * @package App\Tests\Entity
 */
class AssetDocumentsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * function testAssetDocuments
     */
    public function testAssetDocuments(): void
    {
        try {
            $assetDocumentJson = '{
            "file_name" : "sourcefile78" ,
            "file_type" : "png" ,
            "file_alias" : "alias 1",
            "file_description" : "The sample",
            "category" : "category1",
            "tag" : "sampleTag",
            "document_content" : "basdsvdffcverrrrrrrrrrrrrrrrrfcvvvvvvvvvfr3g4h45y5y45t45y4546y45yy4"
            }';
            $param = json_decode($assetDocumentJson, true);
            $assetDocuments = new AssetDocuments();
            $assetSingleDocument = new Document();

            $assetSingleDocument->setName($param['file_name']);
            $assetSingleDocument->setFilename($param['file_name']);

            $assetSingleDocument->setDescription($param['file_description']);

            $assetSingleDocument->setType($param['file_type']);

            $assetSingleDocument->setCategory($param['category']);

            $assetSingleDocument->setAlias($param['file_alias']);

            $assetSingleDocument->setTag($param['tag']);

            $assetDocuments->setDocument($assetSingleDocument);

            $this->assertEquals(
                $assetDocuments->getDocument()->getName(),
                $param['file_name'],
            );
            $this->assertEquals(
                $assetDocuments->getDocument()->getFilename(),
                $param['file_name'],
            );
            $this->assertEquals(
                $assetDocuments->getDocument()->getDescription(),
                $param['file_description'],
            );
            $this->assertEquals(
                $assetDocuments->getDocument()->getType(),
                $param['file_type'],
            );
            $this->assertEquals(
                $assetDocuments->getDocument()->getCategory(),
                $param['category'],
            );
            $this->assertEquals(
                $assetDocuments->getDocument()->getAlias(),
                $param['file_alias'],
            );
            $this->assertEquals(
                $assetDocuments->getDocument()->getTag(),
                $param['tag'],
            );

            //unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }
}
