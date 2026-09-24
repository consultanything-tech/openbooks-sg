<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->recent();

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from.' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to.' 23:59:59');
        }

        if ($request->filled('search')) {
            $query->where('description', 'like', '%'.$request->search.'%');
        }

        $logs = $query->paginate(50)->appends($request->query());
        $users = User::orderBy('name')->get();

        $actions = ['created', 'updated', 'deleted', 'payment_recorded', 'marked_sent', 'duplicated', 'reconciled', 'applied', 'login', 'logout'];
        $modelTypes = ['Invoice', 'Bill', 'Customer', 'Vendor', 'Item', 'BankAccount', 'CreditNote', 'Company', 'Transaction'];

        return view('activity-log.index', compact('logs', 'users', 'actions', 'modelTypes'));
    }
}
