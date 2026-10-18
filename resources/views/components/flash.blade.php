@if (session('status'))
    <div class="toast" role="status" data-toast>
        <x-icon name="check" />
        <span>{{ session('status') }}</span>
        <button type="button" class="icon-btn" style="width:32px;height:32px" aria-label="Tutup" data-dismiss><x-icon name="close" class="icon icon-sm" /></button>
    </div>
@endif
@if ($errors->any())
    <div class="notice notice-danger mb-2" role="alert">
        <x-icon name="warning" />
        <div>
            <strong>Periksa kembali isian Anda:</strong>
            <ul class="alert-list">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    </div>
@endif
