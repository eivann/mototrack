<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'action' => 'nullable|string|in:created,updated,deleted,soft_deleted,restored',
            'model_type' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = AuditLog::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('model_type')) {
            $query->where('auditable_type', $request->model_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->latest('id')->paginate(20)->withQueryString();
        $users = User::orderBy('name')->get();

        $models = [
            \App\Models\InventoryItem::class => 'Inventory Item',
            \App\Models\DamagedItem::class   => 'Damaged Item',
            \App\Models\ProductReturn::class => 'Product Return',
            \App\Models\Sale::class          => 'Sale Record',
        ];

        return view('admin.audit_logs.index', compact('logs', 'users', 'models'));
    }
}
