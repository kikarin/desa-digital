# Changelog - Program Bantuan

## [2026-01-20] - Fitur Jadwal Penyaluran & Status PENYALURAN

### Added
- **Status PENYALURAN**: Status baru untuk program bantuan yang sedang dalam masa penyaluran (antara jam mulai - jam selesai)
- **Jadwal Penyaluran**: Field tanggal, jam mulai, dan jam selesai pengambilan di program bantuan
- **Auto-Update Status**: Status program otomatis berubah berdasarkan jadwal:
  - `PROSES`: Sebelum masuk jadwal
  - `PENYALURAN`: Sudah masuk jadwal (antara jam mulai - jam selesai)
  - `SELESAI`: Sudah lewat jadwal
- **Validasi Jadwal**: Admin dan user tidak bisa melakukan penyaluran jika belum sesuai jadwal
- **Absen Mandiri**: User bisa absen sendiri via PWA dengan upload foto bukti
- **Notifikasi Admin**: Admin mendapat notifikasi ketika user melakukan absen mandiri
- **Foto Bukti**: Field untuk menyimpan foto bukti pengambilan bantuan
- **Filter Absen Mandiri**: Filter di halaman penerima untuk melihat yang absen mandiri

### Changed
- **Status Program**: Enum status diubah dari `['PROSES', 'SELESAI']` menjadi `['PROSES', 'PENYALURAN', 'SELESAI']`
- **Tanggal Penyaluran**: Diubah dari `date` menjadi `datetime` untuk menyimpan waktu penyaluran
- **Detail Program**: Menampilkan jadwal penyaluran dan desil target
- **Detail Penerima**: Menampilkan jadwal penyaluran program dan foto bukti (jika ada)

### API Changes
- **GET /api/pwa/program-bantuan/riwayat-saya**: Response sekarang include:
  - `status_program_label`: Label status program (Proses/Penyaluran/Selesai)
  - `jadwal_pengambilan`: Object dengan tanggal, jam_mulai, jam_selesai, dan label
- **POST /api/pwa/program-bantuan/riwayat-saya/{id}/absen-mandiri**: Endpoint baru untuk absen mandiri
- **GET /api/pwa/program-bantuan/riwayat-saya/{id}/perwakilan-options**: Endpoint untuk mendapatkan list perwakilan

### Database Changes
- Migration: `2026_01_20_100002_add_jadwal_to_assistance_programs_table.php`
  - Tambah `tanggal_penyaluran` (date)
  - Tambah `jam_mulai_pengambilan` (time)
  - Tambah `jam_selesai_pengambilan` (time)
- Migration: `2026_01_20_100003_add_foto_bukti_to_assistance_recipients_table.php`
  - Ubah `tanggal_penyaluran` dari date ke datetime
  - Tambah `foto_bukti_pengambilan` (string)
  - Tambah `absen_mandiri` (boolean)
- Migration: `2026_01_20_100004_add_status_penyaluran_to_assistance_programs_table.php`
  - Update enum status untuk include `PENYALURAN`

### Command
- **program-bantuan:auto-update-status**: Command untuk auto-update status program berdasarkan jadwal
  - Berjalan setiap jam via scheduler
  - Update status PROSES → PENYALURAN → SELESAI berdasarkan waktu

### Frontend Changes
- **Form Program Bantuan**: Tambah field jadwal (tanggal, jam mulai, jam selesai)
- **Index Program Bantuan**: Tampilkan status PENYALURAN dengan badge biru
- **Detail Program Bantuan**: Tampilkan jadwal penyaluran dan desil target
- **Index Penerima**: Tambah kolom "Absen Mandiri" dan "Foto Bukti"
- **Detail Penerima**: Tampilkan foto bukti dengan preview dan download

### Security
- Validasi jadwal untuk mencegah penyaluran di luar waktu yang ditentukan
- Validasi penerima lapangan harus dari keluarga yang sama
