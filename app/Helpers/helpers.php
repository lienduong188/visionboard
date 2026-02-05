<?php

/**
 * Format a number for display:
 * - Max 2 decimal places, removes trailing zeros (2.50 → "2.5", 2.10 → "2.1")
 * - Whole numbers display without decimals (2.00 → "2")
 *
 * @param float|int|null $value
 * @return string
 */
function formatNumber($value)
{
    if ($value === null || $value === '') {
        return '0';
    }

    $num = floatval($value);

    // Round to 2 decimal places
    $rounded = round($num, 2);

    // Check if it's a whole number
    if ($rounded == floor($rounded)) {
        return number_format($rounded, 0);
    }

    // For decimals, format then remove trailing zeros
    $formatted = number_format($rounded, 2);
    return rtrim(rtrim($formatted, '0'), '.');
}
