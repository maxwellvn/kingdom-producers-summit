<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;

/**
 * The current edition's name, date and venue, edited from the admin panel.
 * Stored as one JSON setting and laid over config('app.summit'), so every page,
 * email and calendar file reads the same values. Blank date or venue reads "to be announced".
 */
final class Edition
{
    public const SETTING = 'summit_edition';
    public const TBA = 'To be announced';

    /** What the admin form edits, with its label. */
    public const FIELDS = [
        'edition'         => 'Edition name',
        'place'           => 'Place, one word',
        'city'            => 'City line',
        'artwork'         => 'Artwork',
        'starts_at'       => 'Starts',
        'ends_at'         => 'Ends',
        'show_time'       => 'Show the start time',
        'venue.name'      => 'Venue name',
        'venue.unit'      => 'Unit or room',
        'venue.street'    => 'Street',
        'venue.town'      => 'Town',
        'venue.region'    => 'Region',
        'venue.postcode'  => 'Postcode',
        'travel.train'    => 'By train',
        'travel.bus'      => 'By bus',
        'travel.car'      => 'By car',
    ];

    /** @return array<string,string> the saved values, flat ('venue.name' => …) */
    public static function saved(): array
    {
        try {
            $data = json_decode(Setting::get(self::SETTING), true);
        } catch (\Throwable) {
            return []; // no database yet: the config file stands
        }

        return is_array($data) ? array_map('strval', $data) : [];
    }

    /** @param array<string,string> $values flat, from the form */
    public static function save(array $values): void
    {
        $clean = [];
        foreach (array_keys(self::FIELDS) as $key) {
            $value = trim((string) ($values[$key] ?? ''));
            if ($key === 'show_time') {
                $value = $value === '1' ? '1' : '0';
            }
            if ($key === 'artwork' && !array_key_exists($value, (array) config('app.artwork'))) {
                $value = 'london';
            }
            if (in_array($key, ['starts_at', 'ends_at'], true)) {
                $value = $value !== '' && strtotime($value) !== false ? date('Y-m-d\TH:i:sP', strtotime($value)) : '';
            }
            $clean[$key] = mb_substr($value, 0, $key === 'travel.train' || $key === 'travel.bus' || $key === 'travel.car' ? 600 : 160);
        }
        Setting::set(self::SETTING, (string) json_encode($clean, JSON_UNESCAPED_UNICODE));
    }

    /** Lay the saved values over the config block and fill in the derived lines. */
    public static function overlay(array $summit): array
    {
        $saved = self::saved();
        if ($saved === []) {
            return $summit;
        }
        foreach ($saved as $key => $value) {
            if (str_contains($key, '.')) {
                [$group, $field] = explode('.', $key, 2);
                $summit[$group][$field] = $value;
            } else {
                $summit[$key] = $value;
            }
        }

        $start = $summit['starts_at'] !== '' ? strtotime($summit['starts_at']) : false;
        if ($start === false) {
            $summit['starts_at'] = $summit['ends_at'] = '';
            $summit['date_day'] = 'Date ' . strtolower(self::TBA);
            $summit['time'] = self::TBA;
            $summit['date_text'] = 'Date ' . strtolower(self::TBA);
        } else {
            $summit['date_day'] = date('l jS F Y', $start);
            // The day can be public before the hour is settled: then no time is shown anywhere.
            $summit['time_hidden'] = ($saved['show_time'] ?? '1') === '0';
            $summit['time'] = $summit['time_hidden'] ? '' : self::clock($start);
            $summit['date_text'] = $summit['date_day'] . ($summit['time_hidden'] ? '' : ', ' . $summit['time']);
            if (($summit['ends_at'] ?? '') === '') {
                $summit['ends_at'] = date('c', $start + 6 * 3600);
            }
        }

        $v = $summit['venue'];
        $summit['venue']['query'] = implode(', ', array_filter([$v['unit'] ?? '', $v['name'] ?? '', $v['street'] ?? '', trim(($v['town'] ?? '') . ' ' . ($v['postcode'] ?? ''))]));

        return $summit;
    }

    /** True once a venue name has been set. */
    public static function hasVenue(): bool
    {
        return trim((string) config('app.summit.venue.name')) !== '';
    }

    public static function showsTime(): bool
    {
        return self::hasDate() && !config('app.summit.time_hidden', false);
    }

    public static function hasDate(): bool
    {
        return trim((string) config('app.summit.starts_at')) !== '';
    }

    /** "12 noon", "2pm", "10.30am" — the way the invitations write it. */
    private static function clock(int $ts): string
    {
        $h = (int) date('G', $ts);
        $m = (int) date('i', $ts);
        if ($h === 12 && $m === 0) {
            return '12 noon';
        }

        return date($m === 0 ? 'ga' : 'g.ia', $ts);
    }
}
