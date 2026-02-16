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
    date_default_timezone_set('Asia/Tehran');

    $timestamp = is_numeric($date) ? $date : strtotime($date);
    if (!$timestamp) $timestamp = time();

    if (!class_exists('IntlDateFormatter')) {
        return date('Y/m/d', $timestamp);
    }

    $date_type = IntlDateFormatter::LONG;
    $time_type = IntlDateFormatter::NONE;
    $pattern = 'd MMMM y';

    if ($format === 'time') {
        $time_type = IntlDateFormatter::SHORT;
        $pattern = 'd MMMM y | HH:mm';
    } elseif ($format === 'full') {
        $time_type = IntlDateFormatter::FULL;
        $pattern = 'd MMMM y ساعت HH:mm';
    } elseif ($format === 'compact') {
        $pattern = 'y/MM/dd';
    }

    $fmt = new IntlDateFormatter(
        'fa_IR@calendar=persian',
        $date_type,
        $time_type,
        'Asia/Tehran',
        IntlDateFormatter::TRADITIONAL,
        $pattern
    );

    return fa_num($fmt->format($timestamp));
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
