<?php

namespace AppBundle\Twig;

use Symfony\Component\Intl\Countries;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TemplateExtension extends AbstractExtension
{
    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            'getFile' => new TwigFilter('get_file', [$this, 'getFile']),
            'getFiles' => new TwigFilter('get_files', [$this, 'getFiles']),
            'getInfo' => new TwigFilter('get_info', [$this, 'getInfo']),
            'countryName' => new TwigFilter('countryName', [$this, 'countryName']),
            'getOfferingStatus' => new TwigFilter('get_status', [$this, 'getOfferingStatus']),
            'youtube' => new TwigFilter('youtube', [$this, 'getYoutubeCode']),
            'jsonDecode' => new TwigFilter('json_decode', [$this, 'jsonDecode']),
            'number_format_short' => new TwigFilter('number_format_short', [$this, 'number_format_short']),

        ];
    }

    /**
     * @param $str
     * @return bool
     */
    public function getYoutubeCode($str)
    {
        $pattern = '#^(?:https?://)?(?:www\.)?(?:youtu\.be/|youtube\.com(?:/embed/|/v/|/watch\?v=|/watch\?.+&v=))([\w-]{11})(?:.+)?$#x';
        preg_match($pattern, $str, $matches);
        return (isset($matches[1])) ? $matches[1] : false;
    }

    /**
     * @param $params
     * @param $type
     * @param string $default
     * @return string
     */
    public function getInfo($params, $type, $default = '')
    {
        if (!empty($params['info'])) {
            foreach ($params['info'] as $data) {
                if ($data['type'] == $type) {
                    return $data['value'];
                }
            }
        }
        return $default;
    }

    /**
     * @param $params
     * @param $key
     * @param array $default
     * @return array
     */
    public function getFile($params, $key, $default = [])
    {
        if (!empty($params['documents'])) {
            foreach ($params['documents'] as $data) {
                if (isset($data['tag']) && $data['tag'] == $key) {
                    $default[$key][] = $data;
                } elseif (isset($data['file_type']) && $data['file_type'] == $key) {
                    $default[$key][] = $data;
                }
            }

            if (!empty($default) && count($default[$key]) >= 1) {
                if ($key === 'avatar' || $key === 'share_certificate') {
                    return $default[$key][count($default[$key]) - 1];
                }
                return $default[$key] = $default[$key][0];
            }
        }
        return $default;
    }

    /**
     * @param $params
     * @param $key
     * @param array $default
     * @return array
     */
    public function getFiles($params, $key, $default = [])
    {
        if (!empty($params['documents'])) {
            foreach ($params['documents'] as $data) {
                if (isset($data['tag']) && $data['tag'] == $key) {
                    $default[$key][] = $data;
                } elseif (isset($data['file_type']) && $data['file_type'] == $key) {
                    $default[$key][] = $data;
                }
            }
        }
        return $default;
    }

    /**
     * @param $countryCode
     * @return null|string
     */
    public function countryName($countryCode)
    {
        return Countries::getName($countryCode);
    }

    public function getOfferingStatus($infos, $lifeCycleStage)
    {
        switch ($lifeCycleStage) {
            case 1:
                return 'Submitted';
                break;
            case 2:
                return 'Rejected';
                break;
            case 3:
                return 'Approved';
                break;
            case 4:
                return 'Restricted';
                break;
            case 5:
                return 'Published';
                break;
            case 6:
                return 'Live';
                break;
            case 7:
                return 'Closing';
                break;
            case 8:
                return 'Settled';
                break;
            case 9:
                return 'Canceled';
                break;
            default:
                return 'Draft';
                break;
        }
    }

    public function jsonDecode($str)
    {
        return json_decode($str);
    }

    public function getName()
    {
        return 'app_extension';
    }


    /**
     * Function that converts a numeric value into an exact abbreviation
     */
    public function number_format_short($n, $precision = 1)
    {
        if ($n == 0) {
            return $n;
        } elseif ($n < 900 && $n > 0) {
            // 0 - 900
            $n_format = number_format($n);
            $suffix = '';
        } elseif ($n < 900000) {
            // 0.9k-850k
            $n_format = number_format($n / 1000, $precision);
            $suffix = 'K';
        } elseif ($n < 900000000) {
            // 0.9m-850m
            $n_format = number_format($n / 1000000, $precision);
            $suffix = 'M';
        }
        // Remove unecessary zeroes after decimal. "1.0" -> "1"; "1.00" -> "1"
        // Intentionally does not affect partials, eg "1.50" -> "1.50"
        if ($precision > 0) {
            $dotzero = '.' . str_repeat('0', $precision);
            $n_format = str_replace($dotzero, '', $n_format);
        }
        return $n_format . $suffix;
    }
}
