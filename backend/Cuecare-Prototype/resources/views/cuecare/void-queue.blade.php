@extends('cuecare.layout')
@section('content')
<h1 class="text-3xl font-bold mb-6">Pending void approvals</h1>
@forelse($requests as $approval)
<div class="bg-white rounded p-5 mb-4">
<p>Transaction #{{$approval->approvable_id}} · ₱{{number_format($approval->approvable?->total??0,2)}} · {{$approval->requester?->name}}</p>
<p>{{$approval->reason_code}} — {{$approval->reason}}</p>
@can('approveVoid',$approval)
<form method="POST" action="{{route('voids.approve',$approval)}}" class="my-3">@csrf<button class="bg-cyan-500 text-white p-2 rounded">Approve void</button></form>
<form method="POST" action="{{route('voids.deny',$approval)}}">@csrf<input name="denial_reason" required placeholder="Denial reason" class="border p-2"><button class="text-red-600">Deny</button></form>
@else<p>You cannot review your own request.</p>@endcan
</div>
@empty<p>No pending void requests.</p>@endforelse
{{$requests->links()}}
@endsection
