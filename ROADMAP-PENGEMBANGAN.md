# Roadmap Pengembangan POS Kafe

Dokumen ini berisi rekomendasi pengembangan berdasarkan audit awal sistem POS Kafe.

## Kondisi Sistem Saat Ini

Sistem sudah memiliki fondasi berikut:

- Login dan autentikasi pengguna.
- Role dan permission.
- Multi-outlet.
- Master data produk, bahan baku, supplier, dan outlet.
- POS kasir.
- Pembelian dan penerimaan barang.
- Batch bahan baku dan FIFO.
- Stock opname.
- Shift kasir dan rekonsiliasi kas.
- Kas kecil.
- Laporan penjualan, inventory, dan shift.
- Export CSV.
- Activity log.

## Tujuan Pengembangan

Pengembangan diarahkan untuk mencapai tujuan berikut:

1. Mencegah kehilangan uang dan stok.
2. Membatasi akses data berdasarkan role dan outlet.
3. Mempercepat proses kasir dan operasional kafe.
4. Memberikan laporan bisnis yang lebih lengkap.
5. Mendukung pertumbuhan outlet dan pelanggan.
6. Meningkatkan stabilitas sistem sebelum digunakan di produksi.

## Prioritas 1: Wajib Sebelum Produksi

### 1. Keamanan dan Hak Akses

- Terapkan permission pada setiap controller dan action.
- Batasi data berdasarkan outlet pengguna.
- Kasir hanya dapat melihat transaksi outlet sendiri.
- Manager hanya dapat mengelola outlet yang ditugaskan.
- Owner dapat melihat seluruh outlet.
- Tambahkan status pengguna: aktif, nonaktif, dan cuti.
- Tambahkan approval untuk void, refund, diskon besar, dan koreksi stok.
- Wajibkan alasan untuk void, refund, diskon, dan koreksi stok.
- Jangan izinkan penghapusan transaksi penting.
- Tambahkan rate limit untuk login.
- Pertimbangkan 2FA untuk owner dan manager.

### 2. Keamanan Transaksi POS

- Gunakan database transaction saat menyimpan transaksi.
- Cegah stok minus.
- Cegah transaksi ganda saat tombol pembayaran diklik berulang.
- Validasi harga dan total transaksi di server.
- Kunci proses pengurangan stok saat transaksi bersamaan.
- Simpan nomor invoice unik.
- Simpan riwayat perubahan transaksi.

### 3. Backup dan Pemulihan

- Jadwalkan backup database otomatis.
- Simpan backup di lokasi terpisah.
- Dokumentasikan prosedur restore.
- Uji restore backup secara berkala.
- Tambahkan health check aplikasi dan database.
- Simpan error log yang mudah ditelusuri.

### 4. Test Fitur Inti

Tambahkan test untuk:

- Login dan authorization.
- Pembatasan outlet.
- Transaksi POS.
- Diskon.
- Pembayaran.
- Pengurangan stok.
- FIFO.
- Pencegahan stok minus.
- Pembukaan dan penutupan shift.
- Selisih kas.
- Void dan refund.
- Multi-outlet.
- Export laporan.

## Prioritas 2: Operasional Kafe

### 0. QR Table Ordering

Pelanggan dapat memesan langsung dari meja melalui QR code unik.

#### Alur Pelanggan

1. Pelanggan scan QR code di meja.
2. Sistem membuka menu berdasarkan meja dan outlet.
3. Pelanggan memilih produk dan modifier.
4. Pelanggan menambahkan catatan pesanan.
5. Pelanggan mengirim order.
6. Order masuk ke kasir dan dapur atau bar.
7. Pelanggan melihat status order.
8. Pelanggan dapat memanggil kasir atau meminta tagihan.
9. Kasir menutup sesi meja setelah pembayaran selesai.

#### Fitur Halaman Pelanggan

- Menu digital tanpa login.
- QR code unik untuk setiap meja.
- Kategori, foto, dan detail produk.
- Modifier produk.
- Keranjang pesanan.
- Catatan pesanan.
- Riwayat order selama sesi meja.
- Status order: menunggu konfirmasi, diterima, sedang dibuat, siap, selesai, dan dibatalkan.
- Tombol panggil kasir.
- Tombol minta bill.
- Tampilan total sementara.

#### Fitur Kasir dan Manager

- Manajemen meja.
- Generate, download, cetak, dan regenerate QR code.
- Aktifkan atau nonaktifkan meja.
- Lihat order berdasarkan meja.
- Terima atau tolak order.
- Ubah status order.
- Gabungkan order dalam satu meja.
- Pisah tagihan.
- Batalkan order dengan alasan.
- Tutup sesi meja.
- Lihat permintaan panggil kasir dan minta bill.

#### Fitur Dapur dan Bar

- Tampilan order masuk.
- Filter order berdasarkan tujuan dapur atau bar.
- Status proses order.
- Waktu order masuk, mulai, dan selesai.
- Penanda pesanan terlambat.
- Notifikasi order baru.

#### Model Pembayaran Tahap Awal

Tahap awal menggunakan model **pesan dari meja, bayar di kasir**.

Alasannya:

- Implementasi lebih sederhana.
- Tidak membutuhkan payment gateway.
- Kasir tetap mengontrol penyelesaian transaksi.
- Risiko pembayaran gagal lebih rendah.
- Order dapat diuji sebelum pembayaran digital diterapkan.

Order dari meja tetap terhubung ke tabel `sales` agar masuk ke laporan penjualan dan rekonsiliasi kas.

#### Pembayaran Tahap Lanjut

Setelah alur order stabil, tambahkan pembayaran langsung dari meja melalui QRIS atau payment gateway.

Kebutuhan tambahan:

- Payment gateway.
- QRIS dinamis.
- Webhook pembayaran.
- Status pembayaran pending, berhasil, gagal, dan kedaluwarsa.
- Pencegahan pembayaran ganda.
- Rekonsiliasi pembayaran otomatis.

#### Struktur Data yang Dibutuhkan

- `tables`: outlet, nomor meja, kapasitas, status, dan token QR.
- `table_sessions`: meja, token sesi, waktu mulai, waktu selesai, dan status.
- `customer_orders`: sesi meja, status, total, dan catatan.
- `customer_order_items`: order, produk, jumlah, harga, dan catatan.
- `order_item_modifiers`: ukuran, topping, level gula, dan level es.
- `kitchen_orders`: order, tujuan dapur atau bar, status, dan waktu proses.
- `table_requests`: sesi meja, tipe permintaan, status, dan waktu ditangani.

#### Keamanan QR Ordering

- QR code tidak boleh memberikan akses admin.
- Token QR dan token sesi harus sulit ditebak.
- Customer tidak boleh melihat order meja lain.
- Customer tidak boleh mengubah harga.
- Harga dan total harus dihitung ulang di server.
- Sistem harus mencegah pengiriman order ganda.
- Kasir dapat menolak atau membatalkan order.
- Sesi meja harus ditutup setelah pembayaran selesai.
- QR lama dapat dinonaktifkan atau diganti.

### 1. Pengembangan POS

- Hold dan recall order.
- Cetak ulang struk.
- Catatan item, seperti `less sugar` atau `tanpa es`.
- Modifier produk.
- Varian ukuran cup.
- Level gula.
- Level es.
- Tambahan topping.
- Paket dan bundling menu.
- Diskon per item.
- Diskon total.
- Voucher dan promo berdasarkan waktu.
- Pembayaran tunai.
- Pembayaran QRIS.
- Pembayaran debit dan kredit.
- Pembayaran e-wallet.
- Split payment.
- Void transaksi.
- Refund sebagian.

### 2. Meja dan Order Dapur

- Nomor meja.
- Tipe order: dine-in, takeaway, dan delivery.
- Denah meja.
- Status meja: kosong, terisi, dipesan, dan dibersihkan.
- Pindah meja.
- Gabung meja.
- Pisah tagihan.
- Status order: baru, diproses, siap, selesai, dan dibatalkan.
- Kitchen Display System.
- Tiket bar dan dapur.
- Waktu mulai dan selesai pesanan.
- Penanda pesanan yang terlambat.

### 3. Inventory dan Resep

- Stok minimum dan maksimum.
- Reorder point otomatis.
- Notifikasi stok kritis.
- Notifikasi bahan mendekati kedaluwarsa.
- Konversi satuan, seperti kilogram ke gram dan liter ke mililiter.
- Transfer stok antar outlet.
- Waste log dengan alasan.
- Stock adjustment dengan approval.
- Costing resep otomatis.
- HPP setiap menu.
- Riwayat perubahan harga bahan.
- Supplier alternatif dan harga supplier.
- Purchase order otomatis berdasarkan reorder point.
- Perbandingan stok sistem dan stok fisik.

## Prioritas 3: Dashboard dan Laporan Bisnis

### Dashboard

Dashboard perlu menampilkan:

- Penjualan hari ini.
- Penjualan bulan ini.
- Jumlah transaksi.
- Nilai transaksi rata-rata.
- Produk terlaris.
- Produk paling tidak laku.
- Margin kotor.
- HPP.
- Laba kotor.
- Stok kritis.
- Bahan yang segera kedaluwarsa.
- Selisih kas per shift.
- Outlet dengan performa terbaik.
- Grafik penjualan per jam.
- Perbandingan penjualan antar periode.

### Laporan

- Penjualan per kategori.
- Penjualan per produk.
- Penjualan per kasir.
- Penjualan per outlet.
- Penjualan per metode pembayaran.
- Laporan diskon.
- Laporan void dan refund.
- Laporan waste.
- Laporan pembelian.
- Laporan hutang supplier.
- Laporan kas kecil.
- Laporan HPP.
- Laporan margin produk.
- Laporan laba rugi sederhana.
- Filter tanggal, outlet, kasir, kategori, dan metode pembayaran.
- Export Excel.
- Export PDF.

## Prioritas 4: Customer dan Loyalty

- Data pelanggan.
- Nomor telepon pelanggan.
- Riwayat pembelian.
- Poin loyalty.
- Level member.
- Voucher pelanggan baru.
- Voucher ulang tahun.
- Stamp card.
- Riwayat komplain pelanggan.
- Notifikasi promosi melalui WhatsApp.

## Prioritas 5: Integrasi Eksternal

- QRIS dinamis.
- Payment gateway.
- Webhook pembayaran.
- Rekonsiliasi pembayaran otomatis.
- Integrasi GoFood.
- Integrasi GrabFood.
- Integrasi ShopeeFood.
- Sinkronisasi menu dan harga.
- Sinkronisasi status order.
- Perhitungan biaya platform delivery.

## Prioritas 6: Peningkatan Teknis

- Tambahkan pagination pada tabel besar.
- Tambahkan index database untuk tanggal, outlet, status, dan foreign key.
- Gunakan queue untuk export laporan dan notifikasi.
- Gunakan cache untuk dashboard.
- Gunakan soft delete untuk master data.
- Tambahkan monitoring kegagalan pembayaran.
- Pisahkan service transaksi dari controller jika logic mulai membesar.
- Pastikan seluruh input dari pengguna tervalidasi di server.

## Roadmap Tahapan

### Fase 1: Keamanan dan Stabilitas

1. Filter outlet berdasarkan pengguna.
2. Perbaiki authorization setiap controller.
3. Amankan transaksi POS dengan database transaction.
4. Cegah stok minus dan transaksi ganda.
5. Tambahkan approval void, refund, diskon, dan koreksi stok.
6. Siapkan backup dan restore.
7. Tambahkan test transaksi inti.
8. Buat dashboard dasar.

### Fase 2: Operasional Kafe

1. Tambahkan modifier produk.
2. Tambahkan data meja dan QR code.
3. Tambahkan sesi meja.
4. Tambahkan customer order dari meja.
5. Hubungkan customer order ke POS dan sales.
6. Tambahkan status order pelanggan.
7. Tambahkan Kitchen Display System.
8. Tambahkan panggil kasir dan minta bill.
9. Tambahkan hold dan recall order.
10. Tambahkan split payment.
11. Tambahkan cetak ulang struk.
12. Tambahkan waste log.
13. Tambahkan transfer stok antar outlet.

### Fase 3: Analitik Bisnis

1. Tambahkan HPP resep.
2. Tambahkan margin produk.
3. Tambahkan laporan laba rugi.
4. Tambahkan forecast stok.
5. Tambahkan analisis produk terlaris dan tidak laku.
6. Tambahkan perbandingan outlet.
7. Tambahkan export Excel dan PDF.
8. Tambahkan notifikasi stok dan kedaluwarsa.

### Fase 4: Pertumbuhan Bisnis

1. Tambahkan database pelanggan.
2. Tambahkan loyalty.
3. Tambahkan voucher.
4. Integrasikan QRIS atau payment gateway.
5. Integrasikan layanan delivery.
6. Tambahkan notifikasi WhatsApp.
7. Optimalkan tampilan kasir untuk tablet dan mobile.

## Rekomendasi Eksekusi

Jangan mengembangkan semua fitur sekaligus. Selesaikan Fase 1 lebih dahulu karena keamanan, stok, transaksi, dan laporan kas menentukan risiko operasional.

Urutan fitur paling penting:

1. Pembatasan outlet dan permission.
2. Konsistensi transaksi dan stok.
3. Approval transaksi berisiko.
4. Backup dan test.
5. Dashboard dan laporan laba.
6. QR ordering dari meja.
7. Modifier, meja, dan order dapur.
8. Customer, loyalty, dan integrasi pembayaran.

## Non-Goals

Fitur berikut tidak perlu dibuat pada tahap awal:

- Aplikasi mobile native terpisah.
- Sistem akuntansi penuh.
- Marketplace internal.
- AI forecasting sebelum data historis cukup.
- Integrasi banyak platform sebelum alur POS stabil.

## Risiko yang Perlu Dipantau

- Data antar outlet tercampur.
- Stok berkurang dua kali saat transaksi bersamaan.
- Transaksi tersimpan dua kali.
- Void dan refund tidak memiliki approval.
- Laporan penjualan tidak sama dengan kas fisik.
- HPP tidak mengikuti perubahan harga bahan.
- Backup tersedia tetapi tidak dapat direstore.
- Permission hanya diterapkan di tampilan, bukan di server.
