<?php

/**
 * Created by PhpStorm.
 * User: khoa.nguyen
 * Date: 12/3/2015
 * Time: 9:56 AM
 */

namespace AppBundle\Util;

class Pagination
{
    /**
     * @param $items
     * @param $totalCount
     * @param $numItemsPerPage
     * @param $currentPageNumber
     * @param int $pageRange
     * @return array
     */
    public static function getPaginationData($totalCount, $numItemsPerPage, $currentPageNumber, $pageRange = 5)
    {
        $pageCount = intval(ceil($totalCount / $numItemsPerPage));
        $current = $currentPageNumber;

        if ($pageCount < $current) {
            $currentPageNumber = $current = $pageCount;
        }

        if ($pageRange > $pageCount) {
            $pageRange = $pageCount;
        }

        $delta = ceil($pageRange / 2);

        if ($current - $delta > $pageCount - $pageRange) {
            $pages = range($pageCount - $pageRange + 1, $pageCount);
        } else {
            if ($current - $delta < 0) {
                $delta = $current;
            }

            $offset = $current - $delta;
            $pages = range($offset + 1, $offset + $pageRange);
        }

        $proximity = floor($pageRange / 2);

        $startPage = $current - $proximity;
        $endPage = $current + $proximity;

        if ($startPage < 1) {
            $endPage = min($endPage + (1 - $startPage), $pageCount);
            $startPage = 1;
        }

        if ($endPage > $pageCount) {
            $startPage = max($startPage - ($endPage - $pageCount), 1);
            $endPage = $pageCount;
        }

        $viewData = [
            'last' => $pageCount,
            'current' => $current,
            'numItemsPerPage' => $numItemsPerPage,
            'first' => 1,
            'pageCount' => $pageCount,
            'totalCount' => $totalCount,
            'pageRange' => $pageRange,
            'startPage' => $startPage,
            'endPage' => $endPage
        ];

        if ($current - 1 > 0) {
            $viewData['previous'] = $current - 1;
        }

        if ($current + 1 <= $pageCount) {
            $viewData['next'] = $current + 1;
        }

        $viewData['pagesInRange'] = $pages;
        $viewData['firstPageInRange'] = min($pages);
        $viewData['lastPageInRange'] = max($pages);

        return $viewData;
    }
}
