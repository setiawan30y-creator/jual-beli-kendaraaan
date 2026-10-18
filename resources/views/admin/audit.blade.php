@extends('layouts.admin')
@section('heading', 'Log audit')
@section('content')
<h1>Log audit platform</h1>
<form class="row mb-2" method="get"><input class="input" style="flex:1" name="aksi" value="{{ $action }}" placeholder="Filter awalan aksi, mis. platform., listing., media.private" aria-label="Filter aksi"><button class="btn btn-outline">Filter</button></form>
@include('partials.audit-table', ['logs' => $logs, 'showTenant' => true])
@endsection
