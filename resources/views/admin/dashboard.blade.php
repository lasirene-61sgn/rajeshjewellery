@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900">Dashboard Overview</h1>
    <p class="mt-1 text-sm text-slate-500">Welcome to your secure admin control panel.</p>
</div>

<!-- Stats Grid (Today) -->
<h2 class="text-lg font-semibold text-slate-800 mb-4 mt-8">Today's Activity</h2>
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-slate-200 p-4 hover:border-indigo-300 transition-colors">
        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Created</dt>
        <dd class="mt-2 text-2xl font-bold text-indigo-600">{{ $counts['today_created'] }}</dd>
    </div>
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-slate-200 p-4 hover:border-blue-300 transition-colors">
        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Allocated</dt>
        <dd class="mt-2 text-2xl font-bold text-blue-600">{{ $counts['today_allocated'] }}</dd>
    </div>
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-slate-200 p-4 hover:border-amber-300 transition-colors">
        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wider">In Process</dt>
        <dd class="mt-2 text-2xl font-bold text-amber-600">{{ $counts['today_in_process'] }}</dd>
    </div>
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-slate-200 p-4 hover:border-purple-300 transition-colors">
        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wider">For Approval</dt>
        <dd class="mt-2 text-2xl font-bold text-purple-600">{{ $counts['today_for_approval'] }}</dd>
    </div>
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-slate-200 p-4 hover:border-emerald-300 transition-colors">
        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Completed</dt>
        <dd class="mt-2 text-2xl font-bold text-emerald-600">{{ $counts['today_completed'] }}</dd>
    </div>
</div>

<!-- Stats Grid (Overall) -->
<h2 class="text-lg font-semibold text-slate-800 mb-4 mt-8">Overall Overview</h2>
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-slate-200 p-6 hover:border-indigo-300 transition-colors">
        <dt class="text-sm font-semibold text-slate-500 uppercase">New / Pending</dt>
        <dd class="mt-2 text-3xl font-extrabold text-indigo-600">{{ $counts['overall_new'] }}</dd>
    </div>

    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-rose-200 bg-rose-50/20 p-6 hover:border-rose-300 transition-colors">
        <dt class="text-sm font-semibold text-rose-600 uppercase">Rejected (Returned)</dt>
        <dd class="mt-2 text-3xl font-extrabold text-rose-600">{{ $counts['overall_rejected'] }}</dd>
    </div>

    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-orange-200 bg-orange-50/20 p-6 hover:border-orange-300 transition-colors">
        <dt class="text-sm font-semibold text-orange-600 uppercase">Overdue</dt>
        <dd class="mt-2 text-3xl font-extrabold text-orange-600">{{ $counts['overall_overdue'] }}</dd>
    </div>

    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-slate-200 p-6">
        <dt class="text-sm font-semibold text-slate-500 uppercase">Total Craftsmen</dt>
        <dd class="mt-2 text-3xl font-extrabold text-slate-900">{{ \App\Models\Craftsman::count() }}</dd>
    </div>
</div>
@endsection