<?php

namespace App\Service\Pagination;

use JMS\Serializer\Annotation as JMS;
use Pagerfanta\Pagerfanta;

#[JMS\ExclusionPolicy('all')]
class PaginatedCollection
{
    public function __construct(
        private Pagerfanta $pagerfanta,
    ) {}

    #[JMS\Expose]
    #[JMS\Groups(['pagination'])]
    #[JMS\VirtualProperty]
    #[JMS\Inline]
    public function getPaginationData()
    {
        return [
            'data' => $this->pagerfanta->getCurrentPageResults(),
            'pagination' => [
                'currentPage' => $this->pagerfanta->getCurrentPage(),
                'hasPreviousPage' => $this->pagerfanta->hasPreviousPage(),
                'hasNextPage' => $this->pagerfanta->hasNextPage(),
                'totalItems' => $this->pagerfanta->getNbResults(),
                'totalPages' => $this->pagerfanta->getNbPages(),
            ],
        ];
    }
}
