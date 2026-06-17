<?php

namespace App\Tests\Dto;

use App\Dto\OfferingAssembler;
use App\Dto\OfferingPatchDTO;
use App\Dto\OfferingPostDTO;
use App\Entity\Asset;
use App\Entity\Offering;
use App\Test\FixtureTestCase;

class OfferingAssemblerTest extends FixtureTestCase
{
    /** @var \App\Dto\OfferingAssembler */
    private $offeringAssembler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->offeringAssembler =
            new OfferingAssembler(static::getContainer()->get(\App\Repository\AssetRepository::class));
    }

    public function testCreateOfferingIncName(): void
    {
        $asset = $this->searchFixtures(Asset::class, [])[0];

        $offeringDTO = new OfferingPostDTO(
            'new offering',
            $asset->getId(),
            0,
            0,
            false,
            250,
            0,
            0,
            0,
            0,
            0,
            0,
        );

        $createdOffering = $this->offeringAssembler->createOffering($offeringDTO);
        $this->assertNotEmpty($createdOffering);
        $this->assertEquals('new offering', $createdOffering->getName());
        $this->assertEquals(250, $createdOffering->getNoOfShares());
        $this->assertEquals($asset->getId(), $createdOffering->getAsset()->getId());
    }

    public function testCreateOfferingExcName(): void
    {
        $asset = $this->searchFixtures(Asset::class, [])[0];

        $offeringDTO = new OfferingPostDTO(
            '',
            $asset->getId(),
            0,
            0,
            false,
            250,
            0,
            0,
            0,
            0,
            0,
            0,
        );

        $createdOffering = $this->offeringAssembler->createOffering($offeringDTO);
        $this->assertNotEmpty($createdOffering);
        $this->assertEquals($asset->getName(), $createdOffering->getName());
        $this->assertEquals(250, $createdOffering->getNoOfShares());
        $this->assertEquals($asset->getId(), $createdOffering->getAsset()->getId());
    }

    public function testUpdateOffering(): void
    {
        $offering = new Offering();
        $offering->setName('orig name');
        $offering->setIsFeatured(true);
        $offering->setExternalCommitments(100);
        $offering->setNoOfShares(500);

        $offeringDTO = new OfferingPatchDTO(
            'updated name',
            null,
            null,
            300,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
        );

        $updatedOffering = $this->offeringAssembler->updateOffering(
            $offering,
            $offeringDTO,
        );
        $this->assertNotEmpty($updatedOffering);
        // updated fields
        $this->assertEquals('updated name', $updatedOffering->getName());
        $this->assertEquals(300, $updatedOffering->getExternalCommitments());
        // fields not updated
        $this->assertEquals(true, $updatedOffering->getIsFeatured());
        $this->assertEquals(500, $updatedOffering->getNoOfShares());
    }
}
