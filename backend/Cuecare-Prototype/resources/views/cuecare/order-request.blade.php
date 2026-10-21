@extends('cuecare.layout')
@section('content')
<h1 class="text-3xl font-bold">POS &amp; F&amp;B order request</h1><p class="text-slate-500 mb-6">Submit items for the Cashier to confirm. This request does not charge a sale.</p>
<form method="POST" action="{{route('orders.request.store')}}" class="bg-white rounded-xl p-6 max-w-2xl space-y-4">@csrf
<input name="table_or_customer" required placeholder="Table number or customer name" class="w-full p-2 border rounded">
<div class="space-y-2">@foreach($menuItems as $item)<label class="flex items-center gap-3 border rounded p-3"><input type="number" min="0" name="items[{{$loop->index}}][quantity]" value="0" class="w-20 p-2 border rounded"><input type="hidden" name="items[{{$loop->index}}][menu_item_id]" value="{{$item->id}}"><span class="flex-1">{{$item->name}}</span><span>₱{{number_format($item->price,2)}}</span></label>@endforeach</div>
<button class="bg-cyan-500 text-white px-4 py-2 rounded font-bold">Submit order request</button></form>
@endsection
