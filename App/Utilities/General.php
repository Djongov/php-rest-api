<?php declare(strict_types=1);

namespace App\Utilities;

class General
{
    public static function fullUri(): string
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }
    public static function randomString(int $length = 64)
    {
        $length = ($length < 4) ? 4 : $length;
        return bin2hex(random_bytes(($length - ($length % 2)) / 2));
    }
    public static function currentBrowser()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }
    // This method catches if current uri is in the array of uris including wildcards
    public static function matchRequestURI($uris)
    {
        // Get the current request URI
        $currentURI = $_SERVER['REQUEST_URI'];

        // Loop through the array of URIs
        foreach ($uris as $uri) {
            // If the URI in the array ends with a wildcard character (*), use strncmp to check if the current URI matches the beginning part of the URI in the array
            if (substr($uri, -1) === '*' && strncmp($currentURI, rtrim($uri, '*'), strlen(rtrim($uri, '*'))) === 0) {
                return true; // Match found
            } elseif ($currentURI === $uri) {
                return true; // Exact match found
            }
        }

        return false; // No match found
    }
    public static function isAssocArray(array $array) : bool
    {
        // If the array is empty, treat it as not associative
        if (empty($array)) {
            return false;
        }
        
        // Check the first key to determine if it's an associative array
        $firstKey = array_key_first($array);
        
        // If the first key is not an integer, it's an associative array
        if (!is_int($firstKey)) {
            return true;
        }
        
        // If the keys are sequential numeric keys starting from 0
        return array_keys($array) !== range(0, count($array) - 1);
    }
    public static function isMultiDimensionalArray(array $array) : bool
    {
        foreach ($array as $element) {
            if (is_array($element)) {
                return true; // If any element is an array, it's multidimensional
            }
        }
        return false; // No element is an array, it's not multidimensional
    }
    public static function parsePhpInfo() : array
    {
        ob_start();
        phpinfo(INFO_ALL);
        $phpinfo = ob_get_clean();

        // Find table data
        preg_match_all('/<tr>(?:.*?<td[^>]*>(.*?)<\/td>.*?<td[^>]*>(.*?)<\/td>)<\/tr>/', $phpinfo, $matches);

        $result = array();
        $currentCategory = '';
        foreach ($matches[1] as $index => $name) {
            if (empty($name)) {
                continue;
            }

            if (strpos($name, '</') !== false) {
                // Skip table headers and footers
                continue;
            }

            if (strpos($name, '<') !== false) {
                // Extract category name
                $currentCategory = strip_tags($name);
                $result[$currentCategory] = array();
            } else {
                // Add key-value pair to the current category
                $result[$currentCategory][$name] = strip_tags($matches[2][$index]);
            }
        }

        return $result;
    }
    // Convert assoc array to indexed array
    public static function assocToIndexed(array $array): array
    {
        return array_map(function ($key, $value) {
            return [$key, $value];
        }, array_keys($array), $array);
    }
    // Random RGBA
    public static function randomRGBA($opacity = 1) : string
    {
        return 'rgba(' . rand(0, 255) . ', ' . rand(0, 255) . ', ' . rand(0, 255) . ', ' . $opacity . ')';
    }
    public static function isValidDatetime(string $datetime) : bool
    {
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d H:i:s.u', // format used by PostgreSQL
            'Y-m-d\TH:i:s',  // format for 'datetime-local' input type
            'Y-m-d\TH:i',    // format for 'datetime-local' input type without seconds
        ];

        foreach ($formats as $format) {
            $d = \DateTime::createFromFormat($format, $datetime);
            if ($d && $d->format($format) === $datetime) {
                return true;
            }
        }

        return false;
    }
    // Convert any date to UTC
    public static function convertToUTC(string $date, string $format = 'Y-m-d H:i') : string
    {
        // get the current timezone
        $timezone = date_default_timezone_get();
        $originalTimezone = new \DateTimeZone($timezone);
        $dateTime = new \DateTime($date, $originalTimezone);
        $dateTime->setTimezone(new \DateTimeZone('UTC'));
        $utcDatetime = $dateTime->format($format);
        return $utcDatetime;
    }
    public static function isDateOrDatetime($dateString)
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
        return $dateTime && $dateTime->format('Y-m-d H:i:s') === $dateString;
    }
    public static function base64url_encode(string $input, int $nopad = 1, int $wrap = 0) : string
    {
        $data  = base64_encode($input);

        if ($nopad) {
            $data = str_replace("=", "", $data);
        }

        $data = strtr($data, '+/=', '-_,');

        if ($wrap) {
            $datalb = "";
            while (mb_strlen($data) > 64) {
                $datalb .= substr($data, 0, 64) . "\n";
                $data = substr($data, 64);
            }
            $datalb .= $data;
            return $datalb;
        } else {
            return $data;
        }
    }

    public static function base64url_decode($input) : string
    {
        return base64_decode(strtr($input, '-_,', '+/='));
    }
    public static function decodeUnicodeString($str) : string
    {
        return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $str);
    }
    public static function countryCodeToLocale($countryCode) : string
    {
        $countryCodeToLocale = [
            'AF' => 'fa_AF',
            'AL' => 'sq_AL',
            'DZ' => 'ar_DZ',
            'AS' => 'en_AS',
            'AD' => 'ca_AD',
            'AO' => 'pt_AO',
            'AI' => 'en_AI',
            'AG' => 'en_AG',
            'AR' => 'es_AR',
            'AM' => 'hy_AM',
            'AW' => 'nl_AW',
            'AU' => 'en_AU',
            'AT' => 'de_AT',
            'AZ' => 'az_AZ',
            'BS' => 'en_BS',
            'BH' => 'ar_BH',
            'BD' => 'bn_BD',
            'BB' => 'en_BB',
            'BY' => 'be_BY',
            'BE' => 'nl_BE',
            'BZ' => 'en_BZ',
            'BJ' => 'fr_BJ',
            'BM' => 'en_BM',
            'BT' => 'dz_BT',
            'BO' => 'es_BO',
            'BA' => 'bs_BA',
            'BW' => 'en_BW',
            'BR' => 'pt_BR',
            'BN' => 'ms_BN',
            'BG' => 'bg_BG',
            'BF' => 'fr_BF',
            'BI' => 'fr_BI',
            'KH' => 'km_KH',
            'CM' => 'fr_CM',
            'CA' => 'en_CA',
            'CV' => 'pt_CV',
            'KY' => 'en_KY',
            'CF' => 'fr_CF',
            'TD' => 'fr_TD',
            'CL' => 'es_CL',
            'CN' => 'zh_CN',
            'CO' => 'es_CO',
            'KM' => 'ar_KM',
            'CG' => 'fr_CG',
            'CR' => 'es_CR',
            'HR' => 'hr_HR',
            'CU' => 'es_CU',
            'CY' => 'el_CY',
            'CZ' => 'cs_CZ',
            'DK' => 'da_DK',
            'DJ' => 'fr_DJ',
            'DM' => 'en_DM',
            'DO' => 'es_DO',
            'EC' => 'es_EC',
            'EG' => 'ar_EG',
            'SV' => 'es_SV',
            'GQ' => 'es_GQ',
            'ER' => 'ti_ER',
            'EE' => 'et_EE',
            'ET' => 'am_ET',
            'FJ' => 'en_FJ',
            'FI' => 'fi_FI',
            'FR' => 'fr_FR',
            'GA' => 'fr_GA',
            'GM' => 'en_GM',
            'GE' => 'ka_GE',
            'DE' => 'de_DE',
            'GH' => 'en_GH',
            'GR' => 'el_GR',
            'GD' => 'en_GD',
            'GT' => 'es_GT',
            'GN' => 'fr_GN',
            'GW' => 'pt_GW',
            'GY' => 'en_GY',
            'HT' => 'ht_HT',
            'HN' => 'es_HN',
            'HK' => 'zh_HK',
            'HU' => 'hu_HU',
            'IS' => 'is_IS',
            'IN' => 'hi_IN',
            'ID' => 'id_ID',
            'IR' => 'fa_IR',
            'IQ' => 'ar_IQ',
            'IE' => 'en_IE',
            'IL' => 'he_IL',
            'IT' => 'it_IT',
            'JM' => 'en_JM',
            'JP' => 'ja_JP',
            'JO' => 'ar_JO',
            'KZ' => 'kk_KZ',
            'KE' => 'sw_KE',
            'KI' => 'en_KI',
            'KP' => 'ko_KP',
            'KR' => 'ko_KR',
            'KW' => 'ar_KW',
            'KG' => 'ky_KG',
            'LA' => 'lo_LA',
            'LV' => 'lv_LV',
            'LB' => 'ar_LB',
            'LS' => 'st_LS',
            'LR' => 'en_LR',
            'LY' => 'ar_LY',
            'LI' => 'de_LI',
            'LT' => 'lt_LT',
            'LU' => 'lb_LU',
            'MO' => 'zh_MO',
            'MK' => 'mk_MK',
            'MG' => 'mg_MG',
            'MW' => 'en_MW',
            'MY' => 'ms_MY',
            'MV' => 'dv_MV',
            'ML' => 'fr_ML',
            'MT' => 'mt_MT',
            'MH' => 'en_MH',
            'MR' => 'ar_MR',
            'MU' => 'mfe_MU',
            'MX' => 'es_MX',
            'FM' => 'en_FM',
            'MD' => 'ro_MD',
            'MC' => 'fr_MC',
            'MN' => 'mn_MN',
            'ME' => 'sr_ME',
            'MA' => 'ar_MA',
            'MZ' => 'pt_MZ',
            'MM' => 'my_MM',
            'NA' => 'en_NA',
            'NR' => 'na_NR',
            'NP' => 'ne_NP',
            'NL' => 'nl_NL',
            'NZ' => 'en_NZ',
            'NI' => 'es_NI',
            'NE' => 'fr_NE',
            'NG' => 'en_NG',
            'NO' => 'no_NO',
            'OM' => 'ar_OM',
            'PK' => 'ur_PK',
            'PW' => 'en_PW',
            'PA' => 'es_PA',
            'PG' => 'tpi_PG',
            'PY' => 'gn_PY',
            'PE' => 'es_PE',
            'PH' => 'en_PH',
            'PL' => 'pl_PL',
            'PT' => 'pt_PT',
            'QA' => 'ar_QA',
            'RO' => 'ro_RO',
            'RU' => 'ru_RU',
            'RW' => 'rw_RW',
            'KN' => 'en_KN',
            'LC' => 'en_LC',
            'VC' => 'en_VC',
            'WS' => 'sm_WS',
            'SM' => 'it_SM',
            'ST' => 'pt_ST',
            'SA' => 'ar_SA',
            'SN' => 'fr_SN',
            'RS' => 'sr_RS',
            'SC' => 'fr_SC',
            'SL' => 'en_SL',
            'SG' => 'en_SG',
            'SK' => 'sk_SK',
            'SI' => 'sl_SI',
            'SB' => 'en_SB',
            'SO' => 'so_SO',
            'ZA' => 'af_ZA',
            'SS' => 'en_SS',
            'ES' => 'es_ES',
            'LK' => 'si_LK',
            'SD' => 'ar_SD',
            'SR' => 'nl_SR',
            'SZ' => 'en_SZ',
            'SE' => 'sv_SE',
            'CH' => 'de_CH',
            'SY' => 'ar_SY',
            'TW' => 'zh_TW',
            'TJ' => 'tg_TJ',
            'TZ' => 'sw_TZ',
            'TH' => 'th_TH',
            'TL' => 'pt_TL',
            'TG' => 'fr_TG',
            'TO' => 'to_TO',
            'TT' => 'en_TT',
            'TN' => 'ar_TN',
            'TR' => 'tr_TR',
            'TM' => 'tk_TM',
            'UG' => 'en_UG',
            'UA' => 'uk_UA',
            'AE' => 'ar_AE',
            'GB' => 'en_GB',
            'US' => 'en_US',
            'UY' => 'es_UY',
            'UZ' => 'uz_UZ',
            'VU' => 'bi_VU',
            'VE' => 'es_VE',
            'VN' => 'vi_VN',
            'YE' => 'ar_YE',
            'ZM' => 'en_ZM',
            'ZW' => 'en_ZW',
        ];
    
        // Default to 'en_US' if the country code is not found
        return $countryCodeToLocale[strtoupper($countryCode)] ?? 'en_US';
    }
}
