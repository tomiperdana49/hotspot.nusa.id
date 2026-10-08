{{-- Panduan dalam Bahasa Indonesia. Struktur sama dengan _en.blade.php. --}}

<section id="start" data-help-section class="card p-6 scroll-mt-6">
    <h2 class="text-lg font-semibold mb-1">Memulai</h2>
    <p class="text-sm text-gray-600 mb-4">Nusa Hotspot mengelola hotspot MikroTik Anda dari satu panel: router, paket internet, voucher, tampilan halaman login, dan pemantauan pelanggan. Login pelanggan dicek oleh server RADIUS Nusa, jadi Anda tidak perlu mengatur user satu per satu di router.</p>
    <ol class="grid sm:grid-cols-3 gap-3 text-sm">
        <li class="rounded-xl border border-gray-200 p-4"><span class="badge bg-brand-50 text-brand-700 mb-2">1</span><div class="font-medium">Hubungkan router</div><p class="text-gray-500 mt-1">Menu <b>Router</b> → Tambah Router. RADIUS diatur otomatis.</p></li>
        <li class="rounded-xl border border-gray-200 p-4"><span class="badge bg-brand-50 text-brand-700 mb-2">2</span><div class="font-medium">Buat paket</div><p class="text-gray-500 mt-1">Menu <b>Paket / Profile</b>: kecepatan, masa aktif, jumlah perangkat.</p></li>
        <li class="rounded-xl border border-gray-200 p-4"><span class="badge bg-brand-50 text-brand-700 mb-2">3</span><div class="font-medium">Buat voucher</div><p class="text-gray-500 mt-1">Menu <b>User</b>: satu per satu atau ratusan sekaligus.</p></li>
    </ol>
    <p class="text-sm text-gray-600 mt-4">Opsional: atur tampilan halaman login di menu <b>Template Login</b>, lalu terapkan ke router.</p>
</section>

<section id="router" data-help-section class="card p-6 scroll-mt-6 space-y-4 text-sm text-gray-700">
    <h2 class="text-lg font-semibold text-gray-900">Menghubungkan router MikroTik</h2>
    <div>
        <h3 class="font-medium text-gray-900 mb-1">Persiapan di router</h3>
        <ul class="list-disc pl-5 space-y-1">
            <li>Router sudah punya <b>hotspot server</b> yang aktif (IP → Hotspot → Hotspot Setup).</li>
            <li>Layanan API aktif: <code class="bg-gray-100 px-1 rounded">/ip service enable api</code> (port default 8728).</li>
            <li>Router bisa dijangkau server: lewat <b>IP publik</b>, atau lewat <b>WireGuard</b> (IP 10.88.x.x) jika router di belakang CGNAT.</li>
            <li>Siapkan username &amp; password router yang punya akses API.</li>
        </ul>
    </div>
    <div>
        <h3 class="font-medium text-gray-900 mb-1">Menambahkan router</h3>
        <p>Buka <b>Router → Tambah Router</b>, isi IP, username, password, dan port API, lalu klik <b>Hubungkan</b>. Sistem otomatis:</p>
        <ul class="list-disc pl-5 space-y-1 mt-1">
            <li>menambahkan server RADIUS Nusa ke router dan mengaktifkan RADIUS incoming (untuk memutus sesi dari panel);</li>
            <li>membuat hotspot server profile dan user profile <code class="bg-gray-100 px-1 rounded">radius-nusa</code>;</li>
            <li>mengaktifkan RADIUS pada hotspot profile yang sedang dipakai;</li>
            <li>mengambil nama router dari System Identity.</li>
        </ul>
    </div>
    <div class="info-box">
        <span>💡</span>
        <span>Ganti password API atau IP router? Buka detail router lalu klik <b>Konfigurasi Ulang</b>. Jika API tidak bisa diakses dari server, gunakan <b>script manual</b> di halaman detail router dan tempel di Terminal MikroTik.</span>
    </div>
    <p>Status <b>Online/Offline</b> diperbarui otomatis setiap 5 menit. Router dianggap offline jika tidak merespons lebih dari 10 menit.</p>
</section>

<section id="profile" data-help-section class="card p-6 scroll-mt-6 space-y-4 text-sm text-gray-700">
    <h2 class="text-lg font-semibold text-gray-900">Paket / Profile</h2>
    <p>Paket menentukan aturan untuk setiap voucher: kecepatan, masa aktif, dan jumlah perangkat.</p>
    <dl class="divide-y divide-gray-100 rounded-xl border border-gray-200">
        <div class="p-3 grid sm:grid-cols-3 gap-1"><dt class="font-medium text-gray-900">Kecepatan</dt><dd class="sm:col-span-2">Upload / download, pakai <code class="bg-gray-100 px-1 rounded">K</code> atau <code class="bg-gray-100 px-1 rounded">M</code>, mis. <code class="bg-gray-100 px-1 rounded">512K</code>, <code class="bg-gray-100 px-1 rounded">5M</code>. Kosongkan jika tidak dibatasi.</dd></div>
        <div class="p-3 grid sm:grid-cols-3 gap-1"><dt class="font-medium text-gray-900">Masa aktif</dt><dd class="sm:col-span-2">Lama voucher berlaku (jam/hari/bulan). Bisa mulai dihitung sejak <b>login pertama</b> atau sejak <b>voucher dibuat</b>.</dd></div>
        <div class="p-3 grid sm:grid-cols-3 gap-1"><dt class="font-medium text-gray-900">Simultaneous Use</dt><dd class="sm:col-span-2">Berapa perangkat yang boleh memakai 1 voucher bersamaan.</dd></div>
        <div class="p-3 grid sm:grid-cols-3 gap-1"><dt class="font-medium text-gray-900">Session / idle timeout</dt><dd class="sm:col-span-2">Putus otomatis setelah sekian detik per sesi / saat tidak ada aktivitas. Hanya berlaku pada sistem <b>Hotspot biasa</b>.</dd></div>
        <div class="p-3 grid sm:grid-cols-3 gap-1"><dt class="font-medium text-gray-900">MikroTik Group</dt><dd class="sm:col-span-2">Biarkan <code class="bg-gray-100 px-1 rounded">radius-nusa</code>, kecuali Anda paham user profile di router.</dd></div>
    </dl>

    <div>
        <h3 class="font-medium text-gray-900 mb-2">Sistem koneksi: Binding atau Hotspot biasa</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border border-gray-200 rounded-xl overflow-hidden">
                <thead class="bg-gray-50 text-gray-500"><tr><th class="p-3"></th><th class="p-3">Binding</th><th class="p-3">Hotspot biasa</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    <tr><td class="p-3 font-medium">Login ulang</td><td class="p-3">Tidak perlu sampai voucher habis</td><td class="p-3">Perlu jika sesi putus</td></tr>
                    <tr><td class="p-3 font-medium">Bandwidth dibagi</td><td class="p-3">Bisa</td><td class="p-3">Tidak (per perangkat)</td></tr>
                    <tr><td class="p-3 font-medium">Riwayat pemakaian</td><td class="p-3">Hanya ±1 menit pertama</td><td class="p-3">Lengkap</td></tr>
                    <tr><td class="p-3 font-medium">Idle / session timeout</td><td class="p-3">Tidak berlaku</td><td class="p-3">Berlaku</td></tr>
                    <tr><td class="p-3 font-medium">Tombol Logout pelanggan</td><td class="p-3">Tidak berfungsi</td><td class="p-3">Berfungsi</td></tr>
                </tbody>
            </table>
        </div>
        <p class="mt-2">Pada <b>Binding</b>, setelah pelanggan login, sistem memindahkan perangkat ke IP binding (bypass) + simple queue dalam ±1 menit. Pada <b>Hotspot biasa</b>, perangkat tetap sebagai sesi hotspot dan dibatasi langsung oleh RADIUS.</p>
    </div>
    <div>
        <h3 class="font-medium text-gray-900 mb-1">Mode bandwidth (khusus Binding)</h3>
        <ul class="list-disc pl-5 space-y-1">
            <li><b>Per perangkat</b>: tiap perangkat dapat kecepatan penuh. 5M × 3 perangkat = masing-masing 5M.</li>
            <li><b>Dibagi</b>: semua perangkat dalam 1 voucher berbagi kecepatan. 5M × 3 perangkat = total 5M.</li>
        </ul>
    </div>
</section>

<section id="user" data-help-section class="card p-6 scroll-mt-6 space-y-4 text-sm text-gray-700">
    <h2 class="text-lg font-semibold text-gray-900">User / Voucher</h2>
    <div>
        <h3 class="font-medium text-gray-900 mb-1">Membuat voucher</h3>
        <ul class="list-disc pl-5 space-y-1">
            <li><b>User Tunggal</b>: isi username &amp; password sendiri, untuk satu pelanggan.</li>
            <li><b>Batch (Massal)</b>: buat hingga 1000 voucher sekaligus. Atur jumlah, panjang kode, prefix (mis. <code class="bg-gray-100 px-1 rounded">WIFI-</code>), dan charset.</li>
        </ul>
    </div>
    <div>
        <h3 class="font-medium text-gray-900 mb-1">Pilihan password pada batch</h3>
        <ul class="list-disc pl-5 space-y-1">
            <li><b>Acak per user</b>: tiap voucher punya password sendiri.</li>
            <li><b>Sama untuk semua</b>: satu password dipakai seluruh batch.</li>
            <li><b>Sama dengan username</b>: wajib dipakai jika Template Login memakai mode <b>"Kode voucher saja"</b>.</li>
        </ul>
        <p class="mt-1">Tips: untuk voucher yang diketik pelanggan di HP, pilih charset <b>Angka</b> atau <b>Huruf besar</b> agar tidak salah ketik huruf besar/kecil.</p>
    </div>
    <div>
        <h3 class="font-medium text-gray-900 mb-1">Status voucher</h3>
        <ul class="list-disc pl-5 space-y-1">
            <li><b>Aktif</b>: bisa dipakai login. <b>Terpakai</b>: sudah pernah login.</li>
            <li><b>Kadaluarsa</b>: masa aktif habis. Voucher kadaluarsa dihapus otomatis dan perangkatnya diputus.</li>
            <li><b>Nonaktif</b>: diblokir manual lewat Edit.</li>
        </ul>
    </div>
    <p>Klik jumlah <b>Online</b> pada daftar user untuk melihat sesi aktif voucher itu dan memutus perangkat tertentu (<b>Kill Session</b>). Centang beberapa user lalu <b>Hapus Terpilih</b> untuk menghapus massal.</p>
</section>

<section id="template" data-help-section class="card p-6 scroll-mt-6 space-y-4 text-sm text-gray-700">
    <h2 class="text-lg font-semibold text-gray-900">Template Login</h2>
    <p>Atur tampilan halaman login hotspot yang dilihat pelanggan saat konek ke WiFi.</p>
    <ol class="list-decimal pl-5 space-y-1">
        <li>Pilih desain (Modern, Minimal, Gelap), warna, bahasa, dan opsional background serta logo. Pratinjau di kanan langsung berubah.</li>
        <li>Isi nama hotspot, teks sambutan, kontak bantuan, footer, dan <b>URL setelah login</b> (kosongkan untuk membuka halaman Status).</li>
        <li>Pilih mode login: <b>Username &amp; password</b> atau <b>Kode voucher saja</b>.</li>
        <li>Klik <b>Simpan template</b>.</li>
        <li>Di bagian <b>Terapkan ke router</b>, klik <b>Terapkan</b> pada setiap router.</li>
    </ol>
    <div class="info-box">
        <span>💡</span>
        <span>Menyimpan template <b>tidak</b> langsung mengubah router. Setiap kali template diubah, klik <b>Terapkan ulang</b>. Router yang perlu diterapkan ulang ditandai "Ada perubahan".</span>
    </div>
    <ul class="list-disc pl-5 space-y-1">
        <li>Saat diterapkan, router mengunduh file template sekali dari hotspot.nusa.id lalu menyimpannya sendiri. Halaman login tetap tampil walau pelanggan belum punya internet.</li>
        <li>Ukuran template dan sisa storage router ditampilkan. Jika storage kurang, penerapan dibatalkan dan halaman lama tetap dipakai.</li>
        <li>Jika unduhan gagal, halaman login lama tetap dipakai, jadi hotspot tidak pernah tanpa halaman login.</li>
        <li><b>Kembalikan bawaan</b> mengembalikan halaman login yang dipakai router sebelumnya.</li>
        <li>Batas file: logo 200 KB, background 500 KB. Gunakan gambar yang sudah dikompres.</li>
    </ul>
</section>

<section id="devices" data-help-section class="card p-6 scroll-mt-6 space-y-3 text-sm text-gray-700">
    <h2 class="text-lg font-semibold text-gray-900">Device Online</h2>
    <p>Daftar perangkat pelanggan yang sedang terkoneksi di semua router Anda: nama perangkat, router, IP, MAC, kecepatan, lama terhubung, dan pemakaian data.</p>
    <p>Klik <b>Putuskan</b> untuk memutus perangkat. Pada sistem Binding, binding dan queue perangkat itu ikut dihapus, sehingga pelanggan harus login lagi.</p>
</section>

<section id="history" data-help-section class="card p-6 scroll-mt-6 space-y-3 text-sm text-gray-700">
    <h2 class="text-lg font-semibold text-gray-900">Riwayat Koneksi</h2>
    <p>Semua sesi login pelanggan selama <b>90 hari terakhir</b>: waktu mulai &amp; selesai, durasi, upload/download, dan alasan putus. Data lebih lama dihapus otomatis.</p>
    <ul class="list-disc pl-5 space-y-1">
        <li>Saring berdasarkan tanggal dan router, atau cari username, nama device, MAC, atau IP.</li>
        <li>Klik <b>Export CSV</b> untuk mengunduh data ke Excel.</li>
        <li>Pada paket <b>Binding</b>, riwayat hanya mencatat sesi login awal (±1 menit). Pilih <b>Hotspot biasa</b> jika butuh catatan pemakaian lengkap.</li>
    </ul>
</section>

<section id="faq" data-help-section class="card p-6 scroll-mt-6 text-sm text-gray-700">
    <h2 class="text-lg font-semibold text-gray-900 mb-3">FAQ &amp; solusi masalah</h2>
    <div class="divide-y divide-gray-100 border border-gray-200 rounded-xl">
        <details class="group p-4"><summary class="cursor-pointer font-medium text-gray-900">Popup / halaman login tidak muncul saat konek WiFi</summary>
            <ul class="list-disc pl-5 mt-2 space-y-1"><li>Lupakan (forget) jaringan WiFi di HP lalu sambungkan ulang.</li><li>Buka situs <code class="bg-gray-100 px-1 rounded">http://</code> biasa, mis. <code class="bg-gray-100 px-1 rounded">http://neverssl.com</code>.</li><li>Perangkat mungkin masih ter-bypass dari login sebelumnya (paket Binding). Putuskan dulu di Device Online.</li><li>Jika baru menerapkan template, cek statusnya di menu Template Login. Bila ragu, klik <b>Terapkan ulang</b>.</li></ul>
        </details>
        <details class="group p-4"><summary class="cursor-pointer font-medium text-gray-900">Voucher tidak bisa login</summary>
            <ul class="list-disc pl-5 mt-2 space-y-1"><li>Cek status voucher di menu User (Kadaluarsa / Nonaktif).</li><li>Jumlah perangkat sudah mencapai batas Simultaneous Use paketnya.</li><li>Mode login "Kode voucher saja" hanya untuk voucher dengan password sama dengan username.</li><li>Kode alfanumerik membedakan huruf besar &amp; kecil.</li></ul>
        </details>
        <details class="group p-4"><summary class="cursor-pointer font-medium text-gray-900">Router berstatus Offline</summary>
            <ul class="list-disc pl-5 mt-2 space-y-1"><li>Pastikan router menyala dan terhubung internet / WireGuard.</li><li>Pastikan layanan API aktif dan port API tidak diblokir firewall.</li><li>Jika IP atau password API berubah, klik <b>Konfigurasi Ulang</b> di detail router.</li></ul>
        </details>
        <details class="group p-4"><summary class="cursor-pointer font-medium text-gray-900">Gagal menerapkan template ke router</summary>
            <ul class="list-disc pl-5 mt-2 space-y-1"><li>Baca pesan merah yang muncul: router offline, gagal mengunduh, atau storage kurang.</li><li>Router harus bisa membuka <b>hotspot.nusa.id</b> (cek DNS &amp; internet router).</li><li>Storage kurang: hapus file lama di menu Files router, atau kecilkan logo/background.</li></ul>
        </details>
        <details class="group p-4"><summary class="cursor-pointer font-medium text-gray-900">Pelanggan harus login ulang terus</summary>
            <p class="mt-2">Itu perilaku normal paket <b>Hotspot biasa</b> saat sesi putus atau terkena idle timeout. Jika ingin pelanggan tidak perlu login ulang sampai voucher habis, ubah paket ke <b>Binding</b>.</p>
        </details>
        <details class="group p-4"><summary class="cursor-pointer font-medium text-gray-900">Kecepatan tidak sesuai paket</summary>
            <ul class="list-disc pl-5 mt-2 space-y-1"><li>Pada Binding, kecepatan diterapkan ±1 menit setelah login.</li><li>Mode <b>Dibagi</b> membagi kecepatan ke semua perangkat dalam satu voucher.</li><li>Pastikan tidak ada queue lain di router yang membatasi lebih ketat.</li></ul>
        </details>
        <details class="group p-4"><summary class="cursor-pointer font-medium text-gray-900">Router saya di belakang CGNAT (tidak punya IP publik)</summary>
            <p class="mt-2">Gunakan koneksi <b>WireGuard</b> ke server Nusa, lalu isi IP WireGuard router (10.88.x.x) saat menambahkan router. Hubungi admin Nusa untuk mendapatkan konfigurasi WireGuard.</p>
        </details>
    </div>
</section>
