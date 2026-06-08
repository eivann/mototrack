<?php

use App\Http\Controllers\Admin\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'owner'])->group(function () {
    Route::get('/admin/audit-logs', [AuditLogController::class, 'index'])->name('admin.audit_logs.index');
});
