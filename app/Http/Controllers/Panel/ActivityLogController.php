<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;

class ActivityLogController extends Controller
{
    public function index()
    {
        $logs = ActivityLog::with(['subject' => fn ($morph) => $morph->constrain([
            OutgoingLetter::class => fn ($q) => $q->select('id', 'registration_no', 'subject'),
            IncomingLetter::class => fn ($q) => $q->select('id', 'agenda_no', 'subject'),
        ])])->orderByDesc('id')->paginate(30);

        return view('panel.logs.index', ['logs' => $logs]);
    }
}
