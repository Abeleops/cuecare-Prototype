<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><script src="https://cdn.tailwindcss.com"></script><script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script></head>
<body class="bg-slate-100 text-slate-900"><div class="min-h-screen flex">
<aside class="w-64 bg-slate-950 text-white p-5"><div class="text-2xl font-black text-cyan-400">CUECARE</div><div class="mt-3 mb-8 border-b border-slate-800 pb-4"><div class="font-semibold">{{auth()->user()->name}}</div><div class="text-xs text-slate-400 capitalize">{{str_replace('_',' ',auth()->user()->role)}}</div></div>
<nav class="space-y-2 text-sm">
@can('void.approve')<a class="block p-2 rounded hover:bg-slate-800" href="{{route('voids.index')}}">Void approvals</a>@endcan
@can('dashboard.overview')<a class="block p-2 rounded hover:bg-slate-800" href="{{route('dashboard')}}">Overview</a>@endcan
@can('pos.access')<a class="block p-2 rounded hover:bg-slate-800" href="{{route('pos')}}">POS &amp; F&amp;B</a>@endcan
@can('orders.request')<a class="block p-2 rounded hover:bg-slate-800" href="{{route('orders.request.form')}}">POS &amp; F&amp;B Order request</a>@endcan
@can('billiards.access')<a class="block p-2 rounded hover:bg-slate-800" href="{{route('billiards')}}">Billiards</a>@endcan
@can('autocare.service')<a class="block p-2 rounded hover:bg-slate-800" href="{{route('autocare')}}">Autocare</a>@endcan
@can('inventory.view')<a class="block p-2 rounded hover:bg-slate-800" href="{{route('inventory')}}">Inventory</a>@endcan
@can('loyalty.view')<a class="block p-2 rounded hover:bg-slate-800" href="{{route('loyalty')}}">Loyalty</a>@endcan
@can('reports.view')<a class="block p-2 rounded hover:bg-slate-800" href="{{route('reports')}}">Reports</a>@endcan
@can('audit.view')<a class="block p-2 rounded hover:bg-slate-800" href="{{route('audit')}}">Audit log</a>@endcan
@can('security.view')<a class="block p-2 rounded hover:bg-slate-800" href="{{route('security')}}">Security</a>@endcan
</nav>
<form method="POST" action="{{route('logout')}}" class="mt-10">@csrf<button class="text-slate-400">Sign out</button></form>
</aside>
<main class="flex-1 p-8">@if(session('success'))<div class="mb-4 p-3 bg-emerald-100 text-emerald-800 rounded">{{session('success')}}</div>@endif @if($errors->any())<div class="mb-4 p-3 bg-red-100 text-red-800 rounded">{{$errors->first()}} <a href="{{route('pos')}}" class="underline ml-2">Open POS to start a shift</a></div>@endif @yield('content')</main>
</div></body></html>
