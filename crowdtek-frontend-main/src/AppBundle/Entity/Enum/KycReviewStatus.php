<?php

namespace AppBundle\Entity\Enum;

enum KycReviewStatus: string
{
    case Open = 'open';
    case PendingSubjectAction = 'pending_subject_action';

    case Ready = 'ready';
    case Closed = 'closed';
    case Completed = 'completed';
}
