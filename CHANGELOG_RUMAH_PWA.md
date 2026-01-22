# Changelog - API Rumah untuk PWA

## [2026-01-20] - Fitur Validasi & Update Data Rumah untuk Warga

### Added
- **API Get Data Rumah**: Endpoint untuk mengambil data rumah user saat ini
  - `GET /api/pwa/rumah-saya`
  - Response include: RT, nomor rumah, pemilik, keterangan, koordinat, foto, dan anggota keluarga
- **API Validasi Nomor Rumah**: Endpoint untuk validasi nomor rumah
  - `POST /api/pwa/rumah-saya/validate-nomor-rumah`
  - Membandingkan input nomor rumah dengan data di database
- **API Update Data Rumah**: Endpoint untuk update data rumah user
  - `POST /api/pwa/rumah-saya/update` (menggunakan POST untuk support multipart/form-data dengan multiple foto)
  - Field yang bisa di-update:
    - Nomor rumah (required)
    - Status kepemilikan (Milik Anda / Bukan)
    - Pemilik (jika Milik Anda, pilih dari anggota keluarga)
    - Keterangan (optional)
    - Latitude/Longitude (optional)
    - Foto (multiple upload dan delete)

### Features
- **Validasi Nomor Rumah**: User bisa validasi apakah nomor rumah yang diinput sesuai dengan data terdaftar
- **Update Data Rumah**: User bisa update data rumah mereka sendiri
- **Pemilik Rumah**:
  - Jika "Milik Anda" → pilih dari anggota keluarga yang sama
  - Jika "Bukan" → pemilik_id dan nama_pemilik di-set null
- **Multiple Foto**: Support upload multiple foto dan hapus foto berdasarkan index
- **Anggota Keluarga**: API return list anggota keluarga untuk dropdown pemilik

### API Endpoints

#### GET /api/pwa/rumah-saya
Mengambil data rumah user saat ini.

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "rt_id": 1,
    "rt": {
      "id": 1,
      "nomor_rt": "01",
      "label": "01 - RW 01 - Galuga"
    },
    "nomor_rumah": "87",
    "pemilik": {
      "is_milik_anda": true,
      "pemilik_id": 123,
      "nama_pemilik": "Budi Santoso"
    },
    "keterangan": "Rumah pribadi",
    "latitude": -6.56422318,
    "longitude": 106.64276468,
    "fotos": ["http://localhost:8000/storage/houses/1/foto1.jpg"],
    "anggota_keluarga": [
      {
        "id": 123,
        "nik": "3201010101010001",
        "nama": "Budi Santoso"
      }
    ]
  }
}
```

#### POST /api/pwa/rumah-saya/validate-nomor-rumah
Validasi nomor rumah.

**Request:**
```json
{
  "nomor_rumah": "87"
}
```

**Response (Valid):**
```json
{
  "success": true,
  "data": {
    "is_valid": true,
    "nomor_rumah_input": "87",
    "nomor_rumah_database": "87",
    "message": "Nomor rumah sesuai dengan data yang terdaftar"
  }
}
```

**Response (Tidak Valid):**
```json
{
  "success": true,
  "data": {
    "is_valid": false,
    "nomor_rumah_input": "88",
    "nomor_rumah_database": "87",
    "message": "Nomor rumah tidak sesuai. Nomor rumah yang terdaftar: 87"
  }
}
```

#### POST /api/pwa/rumah-saya/update
Update data rumah (menggunakan POST untuk support multipart/form-data).

**Request (FormData):**
```
nomor_rumah: "87"
is_milik_anda: true
pemilik_id: 123
keterangan: "Rumah pribadi dengan halaman luas"
latitude: -6.56422318
longitude: 106.64276468
fotos[]: [File, File] // Multiple files
deleted_fotos[]: [0, 1] // Array index foto yang akan dihapus
```

**Request (Jika Bukan Milik Anda):**
```
nomor_rumah: "87"
is_milik_anda: false
keterangan: "Rumah kontrakan"
latitude: -6.56422318
longitude: 106.64276468
```

**Response:**
```json
{
  "success": true,
  "message": "Data rumah berhasil diperbarui",
  "data": {
    "id": 1,
    "nomor_rumah": "87",
    "rt": {...},
    "pemilik": {
      "is_milik_anda": true,
      "pemilik_id": 123,
      "nama_pemilik": "Budi Santoso"
    },
    "keterangan": "Rumah pribadi dengan halaman luas",
    "latitude": -6.56422318,
    "longitude": 106.64276468,
    "fotos": [...]
  }
}
```

### Validation Rules
- `nomor_rumah`: required, string, max 50 karakter
- `is_milik_anda`: required, boolean
- `pemilik_id`: required_if is_milik_anda = true, exists in residents table
- `keterangan`: optional, string, max 500 karakter
- `latitude`: optional, numeric, between -90 to 90
- `longitude`: optional, numeric, between -180 to 180
- `fotos`: optional, array of images, max 5MB per file, format: jpeg, png, jpg
- `deleted_fotos`: optional, array of indexes

### Security
- Hanya user yang terautentikasi dan memiliki resident_id yang bisa akses
- Pemilik hanya bisa dipilih dari anggota keluarga yang sama (dari house yang sama)
- Validasi pemilik harus dari keluarga yang terhubung dengan rumah user

### Technical Notes
- Menggunakan POST method untuk update karena PUT tidak support multipart/form-data dengan baik untuk multiple files
- Handle multipart/form-data dengan merge $_POST dan $_FILES
- Support multiple foto upload dan delete berdasarkan index
- Foto disimpan di `storage/houses/{house_id}/`
- Transaction digunakan untuk memastikan data konsisten
