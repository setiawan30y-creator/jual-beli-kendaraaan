{{-- Panduan instalasi manual untuk browser tanpa prompt instalasi (Blueprint UI §26) --}}
<dialog id="install-help" aria-labelledby="install-help-title">
    <div class="card-body">
        <h3 id="install-help-title">Pasang aplikasi {{ $brandName }}</h3>
        <p class="muted">Browser ini tidak menyediakan tombol instalasi otomatis. Pasang secara manual:</p>
        <div data-guide="ios" hidden>
            <ol><li>Ketuk ikon <strong>Bagikan</strong> di Safari.</li><li>Pilih <strong>Tambahkan ke Layar Utama</strong>.</li><li>Ketuk <strong>Tambah</strong>.</li></ol>
        </div>
        <div data-guide="android" hidden>
            <ol><li>Buka menu browser (⋮).</li><li>Pilih <strong>Instal aplikasi</strong> atau <strong>Tambahkan ke layar utama</strong>.</li></ol>
        </div>
        <div data-guide="desktop" hidden>
            <ol><li>Gunakan Chrome atau Edge versi terbaru.</li><li>Klik ikon instal di bilah alamat, atau buka menu browser → <strong>Instal {{ $brandName }}</strong>.</li></ol>
        </div>
        <p class="small muted">Aplikasi memerlukan koneksi internet untuk data terbaru; transaksi tidak dapat dilakukan saat offline.</p>
        <form method="dialog" class="row" style="justify-content:flex-end"><button class="btn btn-primary">Mengerti</button></form>
    </div>
</dialog>
