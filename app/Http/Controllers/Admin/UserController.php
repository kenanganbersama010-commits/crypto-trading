<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdjustmentHistory;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $accountStatus = $request->input('account_status');
        $kycStatus = $request->input('kyc_status');

        $users = User::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($accountStatus, fn ($query, $status) => $query->where('account_status', $status))
            ->when($kycStatus, fn ($query, $status) => $query->where('kyc_status', $status))
            ->with(['wallets:id,user_id,asset,balance'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user): View
    {
        $user->load('wallets');

        $trades = $user->trades()->latest()->paginate(10, ['*'], 'trades_page')->withQueryString();
        $deposits = $user->deposits()->latest()->paginate(10, ['*'], 'deposits_page')->withQueryString();
        $withdrawals = $user->withdrawals()->latest()->paginate(10, ['*'], 'withdrawals_page')->withQueryString();

        return view('admin.users.show', compact('user', 'trades', 'deposits', 'withdrawals'));
    }

    public function freeze(User $user): RedirectResponse
    {
        abort_if($user->role !== 'user', 403);

        if ($user->account_status === 'frozen') {
            return redirect()->route('admin.users.show', $user)->with('status', 'account-already-frozen');
        }

        try {
            $user->update(['account_status' => 'frozen']);
        } catch (Throwable $e) {
            Log::error('Admin freeze action failed.', ['admin_id' => auth()->id(), 'target_user_id' => $user->id, 'exception' => $e->getMessage()]);

            return redirect()->route('admin.users.show', $user)->with('error', 'account-freeze-failed');
        }

        return redirect()->route('admin.users.show', $user)->with('status', 'account-frozen');
    }

    public function unfreeze(User $user): RedirectResponse
    {
        abort_if($user->role !== 'user', 403);

        if ($user->account_status !== 'frozen') {
            return redirect()->route('admin.users.show', $user)->with('status', 'account-already-active');
        }

        try {
            $user->update(['account_status' => 'active']);
        } catch (Throwable $e) {
            Log::error('Admin unfreeze action failed.', ['admin_id' => auth()->id(), 'target_user_id' => $user->id, 'exception' => $e->getMessage()]);

            return redirect()->route('admin.users.show', $user)->with('error', 'account-unfreeze-failed');
        }

        return redirect()->route('admin.users.show', $user)->with('status', 'account-unfrozen');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        abort_if($user->role !== 'user', 403);

        $validated = $request->validateWithBag('resetLoginPassword', [
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        try {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        } catch (Throwable $e) {
            Log::error('Admin reset login password action failed.', ['admin_id' => auth()->id(), 'target_user_id' => $user->id, 'exception' => $e->getMessage()]);

            return redirect()->route('admin.users.show', $user)->with('error', 'password-reset-failed');
        }

        return redirect()->route('admin.users.show', $user)->with('status', 'password-reset');
    }

    public function resetWithdrawalPassword(Request $request, User $user): RedirectResponse
    {
        abort_if($user->role !== 'user', 403);

        $validated = $request->validateWithBag('resetWithdrawalPassword', [
            'withdrawal_password' => ['required', 'digits:6', 'confirmed'],
        ]);

        try {
            $user->update([
                'withdrawal_password' => Hash::make($validated['withdrawal_password']),
            ]);
        } catch (Throwable $e) {
            Log::error('Admin reset withdrawal password action failed.', ['admin_id' => auth()->id(), 'target_user_id' => $user->id, 'exception' => $e->getMessage()]);

            return redirect()->route('admin.users.show', $user)->with('error', 'withdrawal-password-reset-failed');
        }

        return redirect()->route('admin.users.show', $user)->with('status', 'withdrawal-password-reset');
    }

    public function adjustBalance(Request $request, User $user): RedirectResponse
    {
        abort_if($user->role !== 'user', 403);

        $action = $request->input('action');

        if (! in_array($action, ['add', 'deduct'], true)) {
            return redirect()->route('admin.users.show', $user)->with('error', 'invalid-adjustment-action');
        }

        $validated = $request->validateWithBag('adjustBalance', [
            'asset' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'regex:/^\d{1,12}(\.\d{1,8})?$/', 'gt:0'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $adminId = auth()->id();

        try {
            DB::transaction(function () use ($user, $validated, $action, $adminId) {
                $wallet = Wallet::where('user_id', $user->id)
                    ->where('asset', $validated['asset'])
                    ->lockForUpdate()
                    ->first();

                if (! $wallet) {
                    throw ValidationException::withMessages([
                        'asset' => 'Wallet for selected asset was not found.',
                    ])->errorBag('adjustBalance');
                }

                $balanceBefore = (string) $wallet->balance;

                if ($action === 'deduct' && bccomp($validated['amount'], $balanceBefore, 8) === 1) {
                    throw ValidationException::withMessages([
                        'amount' => 'Insufficient balance. Available balance: '.rtrim(rtrim($balanceBefore, '0'), '.').' '.$wallet->asset.'.',
                    ])->errorBag('adjustBalance');
                }

                $balanceAfter = $action === 'deduct'
                    ? bcsub($balanceBefore, $validated['amount'], 8)
                    : bcadd($balanceBefore, $validated['amount'], 8);

                $wallet->update(['balance' => $balanceAfter]);

                AdjustmentHistory::create([
                    'admin_id' => $adminId,
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'asset' => $wallet->asset,
                    'action' => $action,
                    'amount' => $validated['amount'],
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'reason' => $validated['reason'],
                ]);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error("Admin {$action} balance action failed.", ['admin_id' => $adminId, 'target_user_id' => $user->id, 'exception' => $e->getMessage()]);

            return redirect()->route('admin.users.show', $user)->with('error', "balance-{$action}-failed");
        }

        return redirect()->route('admin.users.show', $user)->with('status', "balance-{$action}ed");
    }
}
