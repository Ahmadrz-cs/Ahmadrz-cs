<?php

namespace App\Entity\Enum;

/**
 * The type of quiz a Question is intended for
 */
enum QuestionType: string
{
    case Appropriateness = 'appropriateness'; // This is a specific FCA term that is distinct from suitability
    case Aml = 'aml';
}
