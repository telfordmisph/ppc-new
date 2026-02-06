<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PickUp;
use App\Traits\MassDeletesByIds;
use Inertia\Inertia;

class PickupController extends Controller
{
  private const SEARCHABLE_COLUMNS = [
    'PARTNAME',
    'PACKAGE',
    'LOTID',
  ];

  use MassDeletesByIds;
  public function massGenocide(Request $request)
  {
    return $this->massDeleteByIds(
      $request,
      PickUp::class
    );
  }

  public function index(Request $request)
  {
    $search = $request->input('search', '');
    $perPage = $request->input('perPage', 100);
    $totalEntries = PickUp::count();

    $partNames = PickUp::query()
      ->selectRaw("*, id_pickup as id")
      ->when($search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
          foreach (self::SEARCHABLE_COLUMNS as $column) {
            $q->orWhere($column, 'like', "%{$search}%");
          }
        });
      })
      ->orderBy('Partname')
      ->paginate($perPage)
      ->withQueryString();

    return Inertia::render('PickupList', [
      'pickups' => $partNames,
      'search' => $search,
      'perPage' => $perPage,
      'totalEntries' => $totalEntries,
    ]);
  }
}
