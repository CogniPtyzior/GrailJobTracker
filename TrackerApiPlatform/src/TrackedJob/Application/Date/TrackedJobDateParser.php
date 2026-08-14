<?php

declare(strict_types=1);

/*
 * Application helper for tracked job date inputs.
 * It accepts the legacy date formats and returns null for absent or invalid values before command mapping.
 */

namespace App\TrackedJob\Application\Date;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

final readonly class TrackedJobDateParser
{
    public static function parseNullable(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $trimmed = trim($value);

        if (!self::isValid($trimmed)) {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed) === 1) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $trimmed, self::utcTimezone());

            return $date instanceof DateTimeImmutable ? $date : null;
        }

        try {
            return new DateTimeImmutable($trimmed, self::utcTimezone());
        } catch (Exception) {
            return null;
        }
    }

    public static function isValid(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $trimmed = trim($value);

        if (preg_match('/^(?<year>\d{4})-(?<month>\d{2})-(?<day>\d{2})$/', $trimmed, $matches) === 1) {
            return checkdate((int) $matches['month'], (int) $matches['day'], (int) $matches['year']);
        }

        $pattern = '/^(?<date>\d{4}-\d{2}-\d{2})T(?<hour>\d{2}):(?<minute>\d{2}):(?<second>\d{2})'
            .'(?:\.\d{1,6})?(?<timezone>Z|[+-](?<offsetHour>\d{2}):(?<offsetMinute>\d{2}))$/';

        if (preg_match($pattern, $trimmed, $matches) !== 1) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $matches['date']));
        $hasOffset = ($matches['timezone'] ?? 'Z') !== 'Z';

        return checkdate($month, $day, $year)
            && (int) $matches['hour'] <= 23
            && (int) $matches['minute'] <= 59
            && (int) $matches['second'] <= 59
            && (!$hasOffset || (int) $matches['offsetHour'] <= 23)
            && (!$hasOffset || (int) $matches['offsetMinute'] <= 59);
    }

    private static function utcTimezone(): DateTimeZone
    {
        return new DateTimeZone('UTC');
    }
}
