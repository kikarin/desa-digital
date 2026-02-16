## PWA - Mode RT (Verifikasi Pengajuan Surat)

Dokumen ini menjelaskan alur dan endpoint API PWA untuk:
- Login PWA (Warga & RT)
- Verifikasi pengajuan surat oleh RT
- Pengajuan proposal oleh warga

---

### 1. Login PWA (Warga & RT)

**Endpoint**
- `POST /api/pwa/login`

**Input**
- `email` (string, required)
- `password` (string, required)

**Perilaku**
- Hanya user dengan role berikut yang diizinkan login ke PWA:
  - `Warga` (role_id = 37)
  - `RT` (role_id = 36)
  - `RW` (role_id = 35) – disiapkan bila nanti diperlukan
- Jika user tidak memiliki salah satu dari role di atas:
  - Response `403` dengan pesan: *"Akun ini tidak memiliki akses ke aplikasi PWA."*
- Jika sukses:
  - Mengembalikan:
    - `user`: id, name, email, resident_id (bisa `null` untuk RT/RW), relasi `resident` (jika ada), relasi `role` (id & name).
    - `token`: Sanctum token untuk dipakai di header `Authorization: Bearer <token>`.

**Catatan Frontend**
- Setelah login, frontend PWA perlu cek `role.name` / `role.id`:
  - Jika `role_id = 37 (Warga)` → tampilkan menu warga (aduan, layanan surat, proposal, dsb).
  - Jika `role_id = 36 (RT)` → tampilkan menu khusus verifikasi RT (lihat bagian 2).

---

### 2. Verifikasi Pengajuan Surat oleh RT (PWA)

Endpoint di bawah ini hanya boleh digunakan oleh akun dengan:
- Role RT (`role_id = 36`)
- Punya relasi `UsersRole` yang berisi `rt_id` (RT sudah terhubung ke data RT tertentu).

#### 2.1. List Pengajuan Surat RT

**Endpoint**
- `GET /api/pwa/pengajuan-surat-rt`

**Deskripsi**
- Mengambil daftar pengajuan surat milik warga yang berada di RT dari user RT yang login.

**Response (contoh bentuk)**
- `success` (bool)
- `data`: array pengajuan (id, nama warga, jenis surat, status, tanggal, dsb) → diisi oleh `PengajuanSuratRepository::customIndex`.
- `meta`: informasi pagination (total, current_page, per_page, dll).

#### 2.2. Detail Pengajuan Surat RT

**Endpoint**
- `GET /api/pwa/pengajuan-surat-rt/{id}`

**Deskripsi**
- Mengambil detail 1 pengajuan surat untuk diverifikasi RT.
- Hanya dapat diakses jika:
  - Pengajuan tersebut berasal dari warga yang rumahnya (`house.rt_id`) sama dengan `rt_id` milik user RT yang login.

**Response (contoh bentuk)**
- `success` (bool)
- `data`: detail pengajuan + atribut, dihasilkan dari `PengajuanSuratRepository::customShow`.

Jika pengajuan bukan dari warga di RT tersebut:
- Response `403` dengan pesan *"Pengajuan surat ini bukan dari warga di RT Anda."*

#### 2.3. Verifikasi Pengajuan Surat RT

**Endpoint**
- `POST /api/pwa/pengajuan-surat-rt/{id}/verifikasi`

**Body**
- `status` (string, required):
  - `diverifikasi_rt` → jika RT menyetujui
  - `ditolak` → jika RT menolak
- `rt_catatan` (string, optional):
  - Catatan/verbal RT (juga dipakai sebagai `alasan_penolakan` jika status `ditolak`).

**Perilaku**
- Validasi:
  - User harus:
    - Terautentikasi (`auth:sanctum`).
    - Memiliki role RT (`role_id = 36`) dengan `rt_id` terisi.
  - Pengajuan harus:
    - Ada.
    - Berasal dari warga di RT tersebut.
    - Memiliki `status = 'menunggu'`.
- Jika valid:
  - Update field:
    - `status` → sesuai input (`diverifikasi_rt` / `ditolak`).
    - `rt_verifikasi_id` → `user->id`.
    - `rt_verifikasi_at` → `now()`.
    - `rt_catatan` → isi dari request.
    - Jika `status = 'ditolak'`, set juga `alasan_penolakan`.
  - Response:
    - `success` = `true`
    - `message` = *"Pengajuan surat berhasil diverifikasi oleh RT."*
    - `data` = detail pengajuan setelah update (`customShow`).

---

### 3. Pengajuan Proposal Warga (PWA)

Endpoint berikut sudah ada dan tetap hanya untuk user dengan `resident_id` (warga).

#### 3.1. Kategori Proposal

**Endpoint**
- `GET /api/pwa/pengajuan-proposal/kategori`

**Deskripsi**
- Mengambil daftar kategori proposal dari tabel `kategori_proposals`.
- Response setiap item:
  - `value` = id kategori
  - `label` = nama kategori

#### 3.2. List & Detail Pengajuan Proposal Saya

**Endpoint**
- `GET /api/pwa/pengajuan-proposal`
  - Daftar proposal yang dibuat oleh user yang login (`filter_created_by = user_id`).
- `GET /api/pwa/pengajuan-proposal/{id}`
  - Detail 1 proposal milik user (hanya bisa jika `created_by = user_id`).

#### 3.3. Buat Pengajuan Proposal

**Endpoint**
- `POST /api/pwa/pengajuan-proposal`

**Body utama**
- `kategori_proposal` (string, required)
- `nomor_telepon_pengaju` (string, required)
- `nama_kegiatan` (string, required)
- `deskripsi_kegiatan` (string, required)
- `usulan_anggaran` (numeric, required)
- `file_pendukung[]` (file opsional, pdf/doc/xls, max 10 MB per file)
- `latitude`, `longitude`, `nama_lokasi`, `alamat` (opsional, lokasi kegiatan)
- `thumbnail_foto_banner` (file image opsional)
- `tanda_tangan_digital` (string base64 opsional)

**Syarat**
- User harus:
  - Terautentikasi (`auth:sanctum`).
  - Memiliki `resident_id` (kalau tidak, akan ditolak dengan pesan agar lengkapi profil).

#### 3.4. Update Pengajuan Proposal

**Endpoint**
- `POST /api/pwa/pengajuan-proposal/{id}/update`

**Body**
- Sama seperti create, namun semua field dibuat `sometimes|required` dan:
  - `deleted_files[]` (string path/filename) untuk menandai file pendukung yang dihapus.

**Perilaku**
- Hanya bisa diupdate jika:
  - `created_by = user_id`
  - Status proposal masih bisa diedit (`menunggu_verifikasi` atau `ditolak` sesuai aturan di model/repository).

---

### 4. Catatan Integrasi Frontend PWA

- Setelah login:
  - Jika `role_id = 37 (Warga)`:
    - Tampilkan seluruh menu warga (aduan, layanan surat, pengajuan proposal, program bantuan, profil resident, dll).
  - Jika `role_id = 36 (RT)`:
    - Sembunyikan menu warga.
    - Tampilkan menu khusus:
      - **Verifikasi Surat Warga**:
        - List → `GET /api/pwa/pengajuan-surat-rt`
        - Detail → `GET /api/pwa/pengajuan-surat-rt/{id}`
        - Aksi verifikasi → `POST /api/pwa/pengajuan-surat-rt/{id}/verifikasi`
    - Halaman profil warga (`/api/pwa/profile`) sebaiknya **tidak** dipanggil untuk RT.
      - Jika butuh halaman akun sederhana, gunakan data dari `/api/pwa/me` saja (nama + role).

---

### 5. Data Resident (Warga) di Response Auth PWA

Beberapa endpoint Auth PWA (`/api/pwa/register`, `/api/pwa/login`, `/api/pwa/me`, `/api/pwa/profile`) sekarang mengembalikan informasi tambahan terkait warga (`resident`):

- **Objek `user.resident` pada `/api/pwa/register`, `/api/pwa/login`, `/api/pwa/me`**
  - `id` (number)
  - `nik` (string)
  - `nama` (string)
  - `tempat_lahir` (string)
  - `tanggal_lahir` (string, format `YYYY-MM-DD`)
  - `jenis_kelamin` (string, `L` atau `P`)
  - `family_status` (number, nullable):
    - `1` = Kepala Keluarga  
    - `2` = Istri  
    - `3` = Anak  
    - `4` = Tambahan (Lainnya)
  - `family_status_text` (string, nullable):
    - Sudah berupa teks siap tampil, contoh:  
      - `"Kepala Keluarga"`  
      - `"Tambahan (Lainnya) - Paman"` (jika ada keterangan tambahan di backend)
  - `status_kawin` (number, nullable):
    - `0` = Tidak Diketahui  
    - `1` = Belum Kawin  
    - `2` = Kawin  
    - `3` = Cerai
  - `status_kawin_text` (string, nullable) – teks siap tampil untuk status kawin.
  - `pendidikan` (string, nullable) – contoh: `SD`, `SMP`, `SMA`, `S1`.
  - `agama` (string, nullable) – contoh: `Islam`, `Kristen`, `Hindu`, dll.
  - `pekerjaan` (string, nullable) – contoh: `Petani`, `Karyawan`, `Wiraswasta`.

- **Endpoint `/api/pwa/profile`**
  - Mengembalikan objek datar (bukan di dalam `user`):
    - `nik`, `nama`, `tempat_lahir`, `tanggal_lahir` (format `DD-MM-YYYY`), `jenis_kelamin` (teks `Laki-laki` / `Perempuan`), `kartu_keluarga` (No KK).
    - Ditambah field baru:
      - `family_status`, `family_status_text`
      - `status_kawin`, `status_kawin_text`
      - `pendidikan`, `agama`, `pekerjaan`

**Catatan Frontend:**
- Untuk tampilan, **disarankan** menggunakan field `*_text` jika tersedia (`family_status_text`, `status_kawin_text`) agar tidak perlu mapping sendiri di FE.
- Jika butuh kode mentah untuk logika (filter, dsb), gunakan `family_status` dan `status_kawin`.
