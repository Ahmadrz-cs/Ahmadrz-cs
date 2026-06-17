<?php

/**
 * Created by PhpStorm.
 */

namespace App\Tests\Entity;

use App\Entity\Document;
use App\Entity\Investment;
use App\Entity\InvestmentDocuments;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class InvestmentDocumentsTest
 * @package App\Tests\Entity
 */
class InvestmentDocumentsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * function testInvestmentDocuments
     */
    public function testInvestmentDocuments(): void
    {
        try {
            $investmentDocumentJson = '{
            "file_name" : "sourcefile78" ,
            "file_type" : "png" ,
            "file_alias" : "alias 1",
            "file_description" : "The sample",
            "category" : "category1",
            "tag" : "sampleTag",
            "document_content" : "basdsvdffcverrrrrrrrrrrrrrrrrfcvvvvvvvvvfr3g4h45y5y45t45y4546y45yy4"
            }';
            $param = json_decode($investmentDocumentJson, true);
            $investmentDocuments = new InvestmentDocuments();
            $investmentSingleDocument = new Document();

            $investmentSingleDocument->setName($param['file_name']);
            $investmentSingleDocument->setFilename($param['file_name']);

            $investmentSingleDocument->setDescription($param['file_description']);

            $investmentSingleDocument->setType($param['file_type']);

            $investmentSingleDocument->setCategory($param['category']);

            $investmentSingleDocument->setAlias($param['file_alias']);

            $investmentSingleDocument->setTag($param['tag']);

            $investmentDocuments->setDocument($investmentSingleDocument);

            $this->assertEquals(
                $investmentDocuments->getDocument()->getName(),
                $param['file_name'],
            );
            $this->assertEquals(
                $investmentDocuments->getDocument()->getFilename(),
                $param['file_name'],
            );
            $this->assertEquals(
                $investmentDocuments->getDocument()->getDescription(),
                $param['file_description'],
            );
            $this->assertEquals(
                $investmentDocuments->getDocument()->getType(),
                $param['file_type'],
            );
            $this->assertEquals(
                $investmentDocuments->getDocument()->getCategory(),
                $param['category'],
            );
            $this->assertEquals(
                $investmentDocuments->getDocument()->getAlias(),
                $param['file_alias'],
            );
            $this->assertEquals(
                $investmentDocuments->getDocument()->getTag(),
                $param['tag'],
            );

            //unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }
}
