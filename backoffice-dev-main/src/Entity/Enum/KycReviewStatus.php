<?php

namespace App\Entity\Enum;

enum KycReviewStatus: string
{
    case Open = 'open';
    case PendingSubjectAction = 'pending_subject_action';

    case Ready = 'ready';
    case Closed = 'closed';
    case Completed = 'completed';

    /**
     * Return list of cases that are considered safe for editing
     * @return KycReviewStatus[]
     */
    public static function editableCases(): array
    {
        return [
            KycReviewStatus::Open,
            KycReviewStatus::PendingSubjectAction,
            KycReviewStatus::Ready,
        ];
    }
}
