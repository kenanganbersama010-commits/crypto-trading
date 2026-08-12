<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdjustmentHistory;
use Illuminate\View\View;

class AdjustmentHistoryController extends Controller
{
    public function index(): View
    {
        $adjustments = AdjustmentHistory::with([
            'user:id,name,email',
            'admin:id,name',
        ])->latest()->get();

        return view('admin.adjustment-history.index', compact('adjustments'));
    }
}
