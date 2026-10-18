@extends('layouts.dealer')
@section('heading', 'Log audit')
@section('content')
<h1>Log audit</h1>
<p class="muted">Riwayat perubahan penting di dealer ini. Log tidak dapat diubah atau dihapus dari aplikasi.</p>
@include('partials.audit-table', ['logs' => $logs, 'showTenant' => false])
@endsection
