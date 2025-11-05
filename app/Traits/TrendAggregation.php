<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;

trait TrendAggregation
{
  public function applyTrendAggregation(
    Builder $query,
    string $period = 'weekly',
    int $lookBack = 3,
    int $offsetDays = 0,
    string $column = 'Date_Loaded',
    string $quantityColumn = 'Qty',
    string $lotColumn = 'Lot_Id'
  ): Builder {
    $now = Carbon::now()->subDays($offsetDays)->endOfDay();

    switch (strtolower($period)) {
      case 'daily':
        $startDate = (clone $now)->subDays($lookBack)->startOfDay();
        $query->selectRaw("DATE($column) as day, SUM($quantityColumn) as total_quantity, COUNT(DISTINCT $lotColumn) as total_lots")
          ->groupBy('day')
          ->orderBy('day');
        break;

      case 'weekly':
        $startDate = (clone $now)->subWeeks($lookBack)->startOfWeek();
        $query->selectRaw("YEAR($column) as year, WEEK($column, 1) as week, SUM($quantityColumn) as total_quantity, COUNT(DISTINCT $lotColumn) as total_lots")
          ->groupBy('year', 'week')
          ->orderBy('year')
          ->orderBy('week');
        break;

      case 'monthly':
        $startDate = (clone $now)->subMonths($lookBack)->startOfMonth();
        $query->selectRaw("YEAR($column) as year, MONTH($column) as month, SUM($quantityColumn) as total_quantity, COUNT(DISTINCT $lotColumn) as total_lots")
          ->groupBy('year', 'month')
          ->orderBy('year')
          ->orderBy('month');
        break;

      case 'quarterly':
        $startDate = (clone $now)->subMonths($lookBack * 3)->startOfQuarter();
        $query->selectRaw("YEAR($column) as year, QUARTER($column) as quarter, SUM($quantityColumn) as total_quantity, COUNT(DISTINCT $lotColumn) as total_lots")
          ->groupBy('year', 'quarter')
          ->orderBy('year')
          ->orderBy('quarter');
        break;

      case 'yearly':
        $startDate = (clone $now)->subYears($lookBack)->startOfYear();
        $query->selectRaw("YEAR($column) as year, SUM($quantityColumn) as total_quantity, COUNT(DISTINCT $lotColumn) as total_lots")
          ->groupBy('year')
          ->orderBy('year');
        break;

      default:
        throw new \InvalidArgumentException("Invalid period: {$period}");
    }

    return $query->whereBetween($column, [$startDate, $now]);
  }
}
