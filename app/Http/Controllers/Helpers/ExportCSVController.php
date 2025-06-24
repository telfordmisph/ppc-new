<?php

namespace App\Http\Controllers\Helpers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExportCSVController extends Controller
{
    public function export()
    {
        $filename = "users.csv";
        $users = User::all();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () use ($users) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Name', 'Email']); // Header row

            foreach ($users as $user) {
                fputcsv($handle, [$user->id, $user->name, $user->email]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
