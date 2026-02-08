<?php

function fa_num($num) {
    if ($num === null || $num === '') return '---';
    if (class_exists('NumberFormatter')) {
        $fmt = new NumberFormatter('fa_IR', NumberFormatter::DECIMAL);
        return $fmt->format($num);
    }
    // Simple fallback
    $western = ['0','1','2','3','4','5','6','7','8','9'];
    $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    return str_replace($western, $persian, (string)$num);
}

function fa_price($num) {
    return fa_num($num);
}

function get_trend_arrow($change) {
    if ($change > 0) return '<span class="trend-arrow trend-up"></span>';
    if ($change < 0) return '<span class="trend-arrow trend-down"></span>';
    return '';
}
