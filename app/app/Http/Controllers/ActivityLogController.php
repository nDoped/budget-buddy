<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $logs = ActivityLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return Inertia::render('ActivityLog', [
            'logs' => $logs,
        ]);
    }
}
