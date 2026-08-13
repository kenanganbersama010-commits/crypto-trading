<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdjustmentHistory;
use App\Models\Deposit;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class DepositController extends Controller
{
    public function index(): View
    {
        $deposits = Deposit::with('user')->latest()->get();

        return view('admin.deposits.index', compact('deposits'));
    }

    public function show(Deposit $deposit): View
    {
        $deposit->load(['user', 'reviewer']);

        return view('admin.deposits.show', compact('deposit'));
    }

    public function approve(Deposit $deposit): RedirectResponse
    {
        $adminId = auth()->id();

        try {
            $result = DB::transaction(function () use ($deposit, $adminId) {
                $locked = Deposit::query()->lockForUpdate()->findOrFail($deposit->id);

                if ($locked->status !== 'pending') {
                    return 'already-processed';
                }

                $wallet = Wallet::where('user_id', $locked->user_id)
                    ->where('asset', $locked->asset)
                    ->lockForUpdate()
                    ->first();

                if (! $wallet) {
                    $wallet = Wallet::create([
                        'user_id' => $locked->user_id,
                        'asset' => $locked->asset,
                        'balance' => '0',
                    ]);
                }

                $balanceBefore = (string) $wallet->balance;
                $balanceAfter = bcadd($balanceBefore, (string) $locked->amount, 8);

                $wallet->update(['balance' => $balanceAfter]);

                $locked->update([
                    'status' => 'approved',
                    'reviewed_by' => $adminId,
                    'reviewed_at' => now(),
                ]);

                AdjustmentHistory::create([
                    'admin_id' => $adminId,
                    'user_id' => $locked->user_id,
                    'wallet_id' => $wallet->id,
                    'asset' => $locked->asset,
                    'type' => 'add',
                    'amount' => $locked->amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'reason' => "Deposit approval (Deposit #{$locked->id})",
                ]);

                return 'approved';
            });
        } catch (Throwable $e) {
            Log::error('Admin deposit approval failed.', ['admin_id' => $adminId, 'deposit_id' => $deposit->id, 'exception' => $e->getMessage()]);

            return redirect()->route('admin.deposits.show', $deposit)->with('error', 'deposit-approve-failed');
        }

        if ($result === 'already-processed') {
            return redirect()->route('admin.deposits.show', $deposit)->with('error', 'deposit-already-processed');
        }

        return redirect()->route('admin.deposits.show', $deposit)->with('status', 'deposit-approved');
    }

    public function reject(Request $request, Deposit $deposit): RedirectResponse
    {
        $validated = $request->validateWithBag('rejectDeposit', [
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $adminId = auth()->id();

        try {
            $result = DB::transaction(function () use ($deposit, $adminId, $validated) {
                $locked = Deposit::query()->lockForUpdate()->findOrFail($deposit->id);

                if ($locked->status !== 'pending') {
                    return 'already-processed';
                }

                $locked->update([
                    'status' => 'rejected',
                    'reviewed_by' => $adminId,
                    'reviewed_at' => now(),
                    'rejection_reason' => $validated['rejection_reason'],
                ]);

                return 'rejected';
            });
        } catch (Throwable $e) {
            Log::error('Admin deposit rejection failed.', ['admin_id' => $adminId, 'deposit_id' => $deposit->id, 'exception' => $e->getMessage()]);

            return redirect()->route('admin.deposits.show', $deposit)->with('error', 'deposit-reject-failed');
        }

        if ($result === 'already-processed') {
            return redirect()->route('admin.deposits.show', $deposit)->with('error', 'deposit-already-processed');
        }

        return redirect()->route('admin.deposits.show', $deposit)->with('status', 'deposit-rejected');
    }
}
