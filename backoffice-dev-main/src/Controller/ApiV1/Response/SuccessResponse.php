<?php

namespace App\Controller\ApiV1\Response;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SuccessResponse extends JsonResponse
{
    /**
     * SuccessResponse constructor.
     *
     * @param array $data
     * @param string $outcome
     * @param array|int $code
     */
    public function __construct(
        $data = [],
        $outcome = 'success',
        $code = Response::HTTP_OK,
    ) {
        $responseTemplate = [
            'outcome' => $outcome,
            'data' => $data,
            'status' => $code,
        ];

        parent::__construct($responseTemplate);
    }
}
