<?php

namespace AppBundle\Entity\Enum;

/**
 * The section, area, or grouping a Question within a set of questions is intended for
 */
enum QuestionArea: int
{
    case ContractualNature = 1;
    case FinancialLoss = 2;
    case IssuerFailureLoss = 3;
    case RegulatedActivity = 4;
    case FscsProtection = 5;
    case Illiquidity = 6;
    case IssuerFailureAdmin = 7;
    case IssuerRole = 8;
    case Diversification = 9;
    case ShareDividend = 10;
    case ShareDilution = 11;
    case ShareRights = 12;
}
