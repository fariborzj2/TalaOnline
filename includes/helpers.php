<?php

function fa_num($num) {
    if ($num === null || $num === '') return '---';

    // Simple fallback/digit replacement is safer for formatted strings like dates
    $western = ['0','1','2','3','4','5','6','7','8','9'];
    $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];

    if (is_numeric($num) && class_exists('NumberFormatter')) {
        $fmt = new NumberFormatter('fa_IR', NumberFormatter::DECIMAL);
        return $fmt->format((float)$num);
    }

    return str_replace($western, $persian, (string)$num);
}

function fa_price($num) {
    return fa_num($num);
}

function jalali_date($date = 'now', $format = 'long') {
    // Handle invalid or "zero" dates
    if (!$date || $date === '0000-00-00 00:00:00' || $date === '0000-00-00') {
        if ($date === 'now') {
            // continue to normal 'now' handling
        } else {
            return '---';
        }
    }

    // Ensure we have a valid DateTime object in Tehran timezone
    $tz = new DateTimeZone('Asia/Tehran');
    try {
        if (!$date || $date === 'now') {
            $dt = new DateTime('now', $tz);
        } elseif (is_numeric($date)) {
            $dt = new DateTime('@' . $date);
            $dt->setTimezone($tz);
        } else {
            // Clean the date string for PHP DateTime
            $clean_date = str_replace('0000-00-00 00:00:00', 'now', (string)$date);
            $dt = new DateTime($clean_date, $tz);

            // If the year is suspiciously low (like -622 Jalali which is near 0 AD), it's likely a zero date
            if ((int)$dt->format('Y') < 1000) {
                return '---';
            }
        }
    } catch (Exception $e) {
        $dt = new DateTime('now', $tz);
    }

    $timestamp = $dt->getTimestamp();

    if (!class_exists('IntlDateFormatter')) {
        // Very basic fallback if Intl is missing
        return fa_num($dt->format('Y/m/d'));
    }

    // Standard formats
    $pattern = 'd MMMM y'; // Default: 28 Bahman 1404

    if ($format === 'weekday') {
        $pattern = 'EEEE d MMMM y'; // Tuesday 28 Bahman 1404
    } elseif ($format === 'time') {
        $pattern = 'd MMMM y | HH:mm';
    } elseif ($format === 'full') {
        $pattern = 'EEEE d MMMM y ساعت HH:mm';
    } elseif ($format === 'compact') {
        $pattern = 'yyyy/MM/dd';
    } elseif ($format === 'day_month') {
        $pattern = 'd MMMM';
    }

    $fmt = new IntlDateFormatter(
        'fa_IR@calendar=persian',
        IntlDateFormatter::FULL,
        IntlDateFormatter::FULL,
        'Asia/Tehran',
        IntlDateFormatter::TRADITIONAL,
        $pattern
    );

    $result = $fmt->format($timestamp);

    // Some Intl implementations might return western digits even for fa_IR
    return fa_num($result);
}

function get_trend_arrow($change) {
    if ($change > 0) return '<span class="trend-arrow trend-up"></span>';
    if ($change < 0) return '<span class="trend-arrow trend-down"></span>';
    return '';
}

function get_base_url() {
    $protocol = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') ? 'http' : 'https';
    $host = $_SERVER['HTTP_HOST'];
    return "$protocol://$host";
}

function get_current_url() {
    return get_base_url() . $_SERVER['REQUEST_URI'];
}

/**
 * Returns optimized asset URL (prefers WebP)
 */
function get_asset_url($path) {
    if (empty($path)) return '/assets/images/gold/gold.webp';

    if (str_starts_with($path, 'http')) return $path;

    $clean_path = ltrim($path, '/');
    $ext = pathinfo($clean_path, PATHINFO_EXTENSION);

    if (strtolower($ext) === 'webp') {
        return '/' . $clean_path;
    }

    // Check if webp exists
    $webp_path = preg_replace('/\.(png|jpg|jpeg)$/i', '.webp', $clean_path);
    if (file_exists(__DIR__ . '/../site/' . $webp_path)) {
        return '/' . $webp_path;
    }

    return '/' . $clean_path;
}
