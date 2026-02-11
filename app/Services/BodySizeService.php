<?php

namespace App\Services;

use App\Repositories\BodySizeRepository;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\BodySize;

class BodySizeService
{
  protected BodySizeRepository $repo;

  public function __construct(BodySizeRepository $repo)
  {
    $this->repo = $repo;
  }

  public function getBodySizeWipAndLot(Request $request)
  {
    $bodySizes = BodySize::orderBy('name')->get(['id', 'name']);
    $bodySizeNames = $bodySizes->pluck('name')->toArray();

    $result = $bodySizes
      ->mapWithKeys(function ($item) {
        return [
          $item->name => [
            'key'   => $item->name,
            'value' => [
              'name' => $item->name,
              'id'   => $item->id,
              'wip'  => ['f1' => 0, 'f2' => 0, 'f3_8mm' => 0, 'f3_12mm' => 0,],
              'lot'  => ['f1' => 0, 'f2' => 0, 'f3_8mm' => 0, 'f3_12mm' => 0,],
            ],
          ],
        ];
      })
      ->toArray();

    Log::info("result: " . json_encode($result));
    $endDate   = Carbon::now()->endOfDay();
    $startDate = Carbon::now()->startOfDay();

    $f1Data = $this->repo->getF1BodySizeWipAndLot($bodySizeNames, $startDate, $endDate);
    $f2Data = $this->repo->getF2BodySizeWipAndLot($bodySizeNames, $startDate, $endDate);
    $f38mmData = $this->repo->getF3BodySizeWipAndLot($bodySizeNames, $startDate, $endDate, "8mm");
    $f312mmData = $this->repo->getF3BodySizeWipAndLot($bodySizeNames, $startDate, $endDate, "12mm");

    $this->applyFactoryData($result, $f1Data, 'f1');
    $this->applyFactoryData($result, $f2Data, 'f2');
    $this->applyFactoryData($result, $f38mmData, 'f3_8mm');
    $this->applyFactoryData($result, $f312mmData, 'f3_12mm');

    $output = array_values($result);

    return $output;
  }

  function applyFactoryData(array &$result, iterable $rows, string $factory)
  {
    foreach ($rows as $row) {
      $key = $row->body_size;

      if (!isset($result[$key])) {
        continue;
      }

      $result[$key]['value']['wip'][$factory] = (int) $row->total_wip;
      $result[$key]['value']['lot'][$factory] = (int) $row->lot_id;
    }
  }
}
