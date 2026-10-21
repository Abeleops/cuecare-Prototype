@if($transaction)
@if($transaction->reversal_of_id)
<span>Reversal of #{{$transaction->reversal_of_id}} · {{$transaction->user?->name}} · {{$transaction->created_at?->format('M d, Y g:i:s A')}}</span>
@elseif($transaction->reversal)
<span class="text-red-600">Voided · approved by {{$transaction->reversal->user?->name}} · {{$transaction->reversal->created_at?->format('M d, Y g:i:s A')}}</span>
@elseif($transaction->voidRequests->where('status','pending')->isNotEmpty())
<span>Void pending approval</span>
@else
@can('requestVoid',$transaction)
<form method="POST" action="{{route('voids.store',$transaction)}}" x-data="{reason:''}" onsubmit="return confirm('Are you sure you want to void transaction #{{$transaction->id}} for {{number_format($transaction->total,2)}}? This cannot be undone.')" class="space-y-2">
@csrf<select name="reason_code" required x-model="reason" class="border rounded p-1"><option value="">Reason code</option>@foreach(['spoilage','damage','customer complaint','data entry error','theft or loss','other'] as $reason)<option value="{{$reason}}">{{$reason}}</option>@endforeach</select>
<input name="reason" placeholder="Note (required for Other)" :required="reason==='other'" class="border rounded p-1">
<button class="text-red-600">Void</button>
</form>
@endcan
@endif
@endif
