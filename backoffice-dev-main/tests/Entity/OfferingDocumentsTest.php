<?php

namespace App\Tests\Entity;

use App\Entity\Document;
use App\Entity\OfferingDocuments;
use Symfony\Component\Validator\Constraints\DateTime;

class OfferingDocumentsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * testOfferingDocuments
     */
    public function testOfferingDocuments(): void
    {
        try {
            $offeringDocumentJson = '{
            "file_name" : "sourcefile78" ,
            "file_type" : "png" ,
            "file_alias" : "alias 1",
            "file_description" : "The sample",
            "category" : "category1",
            "tag" : "sampleTag",
            "document_content" : "basdsvdffcverrrrrrrrrrrrrrrrrfcvvvvvvvvvfr3g4h45y5y45t45y4546y45yy4"
            }';
            $param = json_decode($offeringDocumentJson, true);
            $offeringDocument = new OfferingDocuments();
            $offeringSingleDocument = new Document();

            $offeringSingleDocument->setName($param['file_name']);
            $offeringSingleDocument->setFilename($param['file_name']);

            $offeringSingleDocument->setDescription($param['file_description']);

            $offeringSingleDocument->setType($param['file_type']);

            $offeringSingleDocument->setCategory($param['category']);

            $offeringSingleDocument->setAlias($param['file_alias']);

            $offeringSingleDocument->setTag($param['tag']);

            $offeringDocument->setDocument($offeringSingleDocument);

            $this->assertEquals(
                $offeringDocument->getDocument()->getName(),
                $param['file_name'],
            );
            $this->assertEquals(
                $offeringDocument->getDocument()->getFilename(),
                $param['file_name'],
            );
            $this->assertEquals(
                $offeringDocument->getDocument()->getDescription(),
                $param['file_description'],
            );
            $this->assertEquals(
                $offeringDocument->getDocument()->getType(),
                $param['file_type'],
            );
            $this->assertEquals(
                $offeringDocument->getDocument()->getCategory(),
                $param['category'],
            );
            $this->assertEquals(
                $offeringDocument->getDocument()->getAlias(),
                $param['file_alias'],
            );
            $this->assertEquals(
                $offeringDocument->getDocument()->getTag(),
                $param['tag'],
            );

            //unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }
}
