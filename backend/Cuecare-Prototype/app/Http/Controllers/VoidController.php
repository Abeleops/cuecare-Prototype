<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestVoidRequest;
use App\Models\ApprovalRequest;
use App\Models\Transaction;
use App\Services\VoidService;
use Illuminate\Http\Request;

/** Displays pending void requests and delegates their review to the void service. */
class VoidController extends Controller
{
    /** Returns the paginated pending transaction void queue. */
    public function index()
    {
        return view('cuecare.void-queue', [
            'requests' => ApprovalRequest::with([
                'requester',
                'approvable',
            ])
                ->where('request_type', 'transaction_void')
                ->where('status', 'pending')
                ->latest()
                ->paginate(25),
        ]);
    }

    /** Submits the supplied transaction's void request and redirects back. */
    public function store(RequestVoidRequest $request, Transaction $transaction, VoidService $service)
    {
        $service->request($transaction, $request->user(), $request->validated());

        return back()->with('success', 'Void request submitted for approval.');
    }

    /** Approves the supplied request through the void service and redirects back. */
    public function approve(Request $http, ApprovalRequest $approval, VoidService $service)
    {
        $service->approve($approval, $http->user());

        return back()->with('success', 'Void approved and reversing entry posted.');
    }

    /** Denies the supplied request with a validated reason and redirects back. */
    public function deny(Request $http, ApprovalRequest $approval, VoidService $service)
    {
        $data = $http->validate([
            'denial_reason' => 'required|string|max:1000',
        ]);
        $service->deny($approval, $http->user(), $data['denial_reason']);

        return back()->with('success', 'Void request denied.');
    }
}
