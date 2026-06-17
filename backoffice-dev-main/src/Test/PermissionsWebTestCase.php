<?php

namespace App\Test;

use App\Test\FixtureTestCase;
use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class PermissionsWebTestCase extends FixtureWebTestCase
{
    /**
     * Superadmin is used in regular functional tests
     * No need to test again through permissions tests
     */

    public static function minAnalystProvider(): \Generator
    {
        yield 'Admin' => [FixtureTestCase::USER_ADMIN, Response::HTTP_OK];
        yield 'Finops' => [FixtureTestCase::USER_FINOPS, Response::HTTP_OK];
        yield 'Ops' => [FixtureTestCase::USER_OPERATIONS, Response::HTTP_OK];
        yield 'Techops' => [FixtureTestCase::USER_TECHOPS, Response::HTTP_OK];
        yield 'Analyst' => [FixtureTestCase::USER_ANALYST, Response::HTTP_OK];
    }

    public static function minTechopsProvider(): \Generator
    {
        yield 'Admin' => [FixtureTestCase::USER_ADMIN, Response::HTTP_OK];
        yield 'Finops' => [FixtureTestCase::USER_FINOPS, Response::HTTP_FORBIDDEN];
        yield 'Ops' => [FixtureTestCase::USER_OPERATIONS, Response::HTTP_FORBIDDEN];
        yield 'Techops' => [FixtureTestCase::USER_TECHOPS, Response::HTTP_OK];
        yield 'Analyst' => [FixtureTestCase::USER_ANALYST, Response::HTTP_FORBIDDEN];
    }

    public static function minOperationsProvider(): \Generator
    {
        yield 'Admin' => [FixtureTestCase::USER_ADMIN, Response::HTTP_OK];
        yield 'Finops' => [FixtureTestCase::USER_FINOPS, Response::HTTP_OK];
        yield 'Ops' => [FixtureTestCase::USER_OPERATIONS, Response::HTTP_OK];
        yield 'Techops' => [FixtureTestCase::USER_TECHOPS, Response::HTTP_FORBIDDEN];
        yield 'Analyst' => [FixtureTestCase::USER_ANALYST, Response::HTTP_FORBIDDEN];
    }

    public static function minFinopsProvider(): \Generator
    {
        yield 'Admin' => [FixtureTestCase::USER_ADMIN, Response::HTTP_OK];
        yield 'Finops' => [FixtureTestCase::USER_FINOPS, Response::HTTP_OK];
        yield 'Ops' => [FixtureTestCase::USER_OPERATIONS, Response::HTTP_FORBIDDEN];
        yield 'Techops' => [FixtureTestCase::USER_TECHOPS, Response::HTTP_FORBIDDEN];
        yield 'Analyst' => [FixtureTestCase::USER_ANALYST, Response::HTTP_FORBIDDEN];
    }

    public static function minAdminProvider(): \Generator
    {
        yield 'Admin' => [FixtureTestCase::USER_ADMIN, Response::HTTP_OK];
        yield 'Finops' => [FixtureTestCase::USER_FINOPS, Response::HTTP_FORBIDDEN];
        yield 'Ops' => [FixtureTestCase::USER_OPERATIONS, Response::HTTP_FORBIDDEN];
        yield 'Techops' => [FixtureTestCase::USER_TECHOPS, Response::HTTP_FORBIDDEN];
        yield 'Analyst' => [FixtureTestCase::USER_ANALYST, Response::HTTP_FORBIDDEN];
    }

    public static function minSuperAdminProvider(): \Generator
    {
        yield 'Admin' => [FixtureTestCase::USER_ADMIN, Response::HTTP_FORBIDDEN];
        yield 'Finops' => [FixtureTestCase::USER_FINOPS, Response::HTTP_FORBIDDEN];
        yield 'Ops' => [FixtureTestCase::USER_OPERATIONS, Response::HTTP_FORBIDDEN];
        yield 'Techops' => [FixtureTestCase::USER_TECHOPS, Response::HTTP_FORBIDDEN];
        yield 'Analyst' => [FixtureTestCase::USER_ANALYST, Response::HTTP_FORBIDDEN];
    }
}
