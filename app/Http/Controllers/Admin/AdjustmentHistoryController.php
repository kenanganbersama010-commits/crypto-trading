<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdjustmentHistory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class AdjustmentHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $asset = $request->input('asset');

        $type = $request->input('type');
        $type = in_array($type, ['add', 'deduct'], true) ? $type : null;

        $fromDate = $this->parseDate($request->input('from_date'));
        $toDate = $this->parseDate($request->input('to_date'));
        $dateRangeInvalid = $fromDate && $toDate && $fromDate->gt($toDate);

        $applyFilters = function () use ($search, $asset, $type, $fromDate, $toDate, $dateRangeInvalid) {
            return AdjustmentHistory::query()
                ->when($search, function (Builder $query, string $search) {
                    $query->whereHas('user', function (Builder $query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->when($asset, fn (Builder $query, string $asset) => $query->where('asset', $asset))
                ->when($type, fn (Builder $query, string $type) => $query->where('type', $type))
                ->when($fromDate && ! $dateRangeInvalid, fn (Builder $query) => $query->whereDate('created_at', '>=', $fromDate->toDateString()))
                ->when($toDate && ! $dateRangeInvalid, fn (Builder $query) => $query->whereDate('created_at', '<=', $toDate->toDateString()));
        };

        $adjustments = $applyFilters()
            ->with(['user:id,name,email', 'admin:id,name'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $totalCount = $adjustments->total();
        $addCount = $applyFilters()->where('type', 'add')->count();
        $deductCount = $applyFilters()->where('type', 'deduct')->count();

        $assets = AdjustmentHistory::query()->select('asset')->distinct()->orderBy('asset')->pluck('asset');

        return view('admin.adjustment-history.index', compact(
            'adjustments',
            'totalCount',
            'addCount',
            'deductCount',
            'assets',
            'dateRangeInvalid',
        ));
    }

    private function parseDate(?string $date): ?Carbon
    {
        if (! $date) {
            return null;
        }

        try {
            return Carbon::parse($date)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
