<?php

namespace App\Support;

use NumberFormatter;

/**
 * Locale-aware number formatting with a graceful fallback (Phase 3F).
 *
 * Laravel's Illuminate\Support\Number requires ext-intl. Production builds may
 * run without it (the CI image used for this project does), so dashboards must
 * never turn a missing extension into a 500. When intl is available the ICU
 * rules win; otherwise a small separator table still respects the common
 * European / American conventions.
 */
class LocaleNumber
{
    /**
     * Locales that write 1.234,56 instead of 1,234.56.
     */
    private const COMMA_DECIMAL = [
        'de', 'fr', 'es', 'it', 'pt', 'nl', 'ru', 'uk', 'pl', 'tr', 'id',
        'vi', 'da', 'nb', 'nn', 'sv', 'fi', 'cs', 'sk', 'hu', 'ro', 'bg',
        'hr', 'sr', 'sl', 'et', 'lv', 'lt', 'el', 'ar', 'fa', 'he', 'ur',
    ];

    /**
     * Locales that group with a space instead of a full stop.
     */
    private const SPACE_GROUPED = [
        'fr', 'ru', 'uk', 'pl', 'sv', 'fi', 'nb', 'nn', 'cs', 'sk', 'lv',
    ];

    /**
     * Format a number for the active (or given) locale.
     */
    public static function format(int|float $number, int $precision = 0, ?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        if (class_exists(NumberFormatter::class)) {
            $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $precision);
            $formatter->setAttribute(NumberFormatter::GROUPING_USED, 1);

            $formatted = $formatter->format($number);

            if ($formatted !== false) {
                return $formatted;
            }
        }

        $language = strtolower(substr($locale, 0, 2));
        $decimal = in_array($language, self::COMMA_DECIMAL, true) ? ',' : '.';

        if ($decimal === ',') {
            $thousands = in_array($language, self::SPACE_GROUPED, true) ? "\u{202F}" : '.';
        } else {
            $thousands = ',';
        }

        return number_format((float) $number, $precision, $decimal, $thousands);
    }
}
