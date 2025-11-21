<?php


namespace App\Traits;

use Carbon\Carbon;
use Exception;
use App\Exceptions\InvalidDateRangeException;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Facades\Log;

trait ParseDateTrait
{
  private const AMBIGUOUS_DATE_FORMATS = ['d/m/Y H:i:s', 'm/d/Y H:i:s'];

  protected function parseDate($value, $fallback = null)
  {
    if ($value === null || $value === '') {
      return $fallback;
    }

    if (is_numeric($value)) {
      try {
        $value = Date::excelToDateTimeObject($value);
      } catch (Exception $e) {
        return $fallback;
      }
    }

    if ($value instanceof \DateTimeInterface) {
      return Carbon::instance($value)->format('Y-m-d H:i:s');
    }

    $value = trim($value);
    $value = preg_replace('/\.\d+$/', '', $value);

    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $value, $matches)) {
      [$full, $part1, $part2, $year] = $matches;
      $format = ((int)$part1 > 12) ? 'd/m/Y H:i:s' : 'm/d/Y H:i:s';

      try {
        return Carbon::createFromFormat($format, $value)->format('Y-m-d H:i:s');
      } catch (Exception $e) {
      }
    }

    try {
      return Carbon::parse($value)->format('Y-m-d H:i:s');
    } catch (Exception $e) {
      return $fallback;
    }
  }

  protected function parseDateRange(?string $dateRange = ''): array
  {
    $dateRange = trim($dateRange ?? '');

    // Default: today if empty
    if ($dateRange === '') {
      $today = now();
      return [
        'start' => $today->copy()->startOfDay()->format('Y-m-d H:i:s'),
        'end'   => $today->copy()->endOfDay()->format('Y-m-d H:i:s'),
      ];
    }

    $dateParts = str_contains($dateRange, ' - ')
      ? explode(' - ', $dateRange)
      : [$dateRange];

    try {
      if (count($dateParts) === 1) {
        $date = Carbon::parse(trim($dateParts[0]));
        $start = $date->copy()->startOfDay();
        $end   = $date->copy()->endOfDay();
      } elseif (count($dateParts) === 2) {
        $startInput = trim($dateParts[0]);
        $endInput   = trim($dateParts[1]);

        $start = Carbon::parse($startInput);
        $end   = Carbon::parse($endInput);

        // Expand single dates to full day
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $startInput)) {
          $start = $start->startOfDay();
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $endInput)) {
          $end = $end->endOfDay();
        }
      } else {
        throw new InvalidDateRangeException('The date range format is invalid.');
      }
    } catch (Exception $e) {
      // Log the technical details for debugging
      Log::warning("Date parsing failed for input '{$dateRange}': " . $e->getMessage());

      // Throw a user-friendly message
      throw new InvalidDateRangeException(
        'Invalid or incomplete date or date range. Please use the format YYYY-MM-DD or YYYY-MM-DD - YYYY-MM-DD.'
      );
    }

    if ($start > $end) {
      throw new InvalidDateRangeException('End date must be greater than or equal to start date.');
    }

    return [
      'start' => $start->format('Y-m-d H:i:s'),
      'end'   => $end->format('Y-m-d H:i:s'),
    ];
  }
}
