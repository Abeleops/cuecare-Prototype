<section class="bg-white rounded p-5 mt-6"><h2 class="font-bold">Transaction status and void requests</h2>
@foreach($transactions as $transaction)<div class="border-t py-3"><span>Transaction #{{$transaction->id}} · ₱{{number_format($transaction->total,2)}}</span>@include('cuecare.void-action',['transaction'=>$transaction])</div>@endforeach
</section>
