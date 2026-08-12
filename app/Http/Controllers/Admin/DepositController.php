<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use Illuminate\View\View;

class DepositController extends Controller
{
    public function index(): View
    {
        $deposits = Deposit::with('user')->latest()->get();

        return view('admin.deposits.index', compact('deposits'));
    }

    public function show(Deposit $deposit): View
    {
        $deposit->load('user');

        return view('admin.deposits.show', compact('deposit'));
    }
}
