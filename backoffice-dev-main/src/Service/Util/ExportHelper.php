<?php

namespace App\Service\Util;

final class ExportHelper
{
    public const SUPPORTED_FORMATS = ['json', 'csv', 'xls'];

    public static function validateExportFormat(string $format): string
    {
        return in_array($format, self::SUPPORTED_FORMATS) ? $format : 'csv';
    }

    public static function generateFileName(string $prefix, string $extension): string
    {
        return $prefix . date(\DateTime::ATOM, time()) . '.' . $extension;
    }
}
