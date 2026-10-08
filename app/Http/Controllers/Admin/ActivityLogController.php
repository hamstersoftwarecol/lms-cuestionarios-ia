<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $days = in_array((int) $request->days, [1, 7, 30, 90], true) ? (int) $request->days : 7;

        return view('admin.activity.index', [
            'logs' => ActivityLog::with('user:id,name,email')
                ->where('created_at', '>=', now()->subDays($days))
                ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', $request->action.'%'))
                ->latest('created_at')
                ->limit(3000)
                ->get(),
            'days' => $days,
            'actions' => ActivityLog::query()->distinct()->orderBy('action')->pluck('action')
                ->map(fn ($action) => explode('.', $action)[0])->unique()->values(),
        ]);
    }
}
