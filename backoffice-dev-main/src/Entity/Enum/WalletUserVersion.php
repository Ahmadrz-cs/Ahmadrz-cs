<?php

namespace App\Entity\Enum;

enum WalletUserVersion: int
{
    // User category upgrade not possible or not wanted, e.g. spam, fraudulent, closed accounts
    case None = -1;

    case Original = 0;

    /**
     * Read more about this update
     * - https://docs.mangopay.com/blog/new-release-shamrock
     * - https://gitlab.com/yielders2/backoffice-dev/-/issues/2107
     */
    case UserCategoryUpdate = 1;

    /**
     * Read more about this update
     * - https://docs.mangopay.com/guides/sca
     * - https://docs.mangopay.com/guides/sca/users
     * - https://gitlab.com/yielders2/backoffice-dev/-/issues/2390
     *
     * This is a superset of UserCategoryUpdate as it expects a user to be of type OWNER
     *
     * If user is on this version, they have successfully completed SCA enrollment
     */
    case UserScaEnrollment = 2;
}
