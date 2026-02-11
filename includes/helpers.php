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

function jalali_date($date = 'now') {
    if (!class_exists('IntlDateFormatter')) {
        return date('Y/m/d');
    }

    $fmt = new IntlDateFormatter(
        'fa_IR@calendar=persian',
        IntlDateFormatter::LONG,
        IntlDateFormatter::NONE,
        'Asia/Tehran',
        IntlDateFormatter::TRADITIONAL,
        'd MMMM y'
    );

    return fa_num($fmt->format(strtotime($date)));
}

function get_trend_arrow($change) {
    if ($change > 0) return '<span class="trend-arrow trend-up"></span>';
    if ($change < 0) return '<span class="trend-arrow trend-down"></span>';
    return '';
}
