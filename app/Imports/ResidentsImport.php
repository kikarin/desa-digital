<?php

namespace App\Imports;

use App\Models\Rws;
use App\Models\Rts;
use App\Models\Houses;
use App\Models\Families;
use App\Models\Residents;
use App\Models\ResidentStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class ResidentsImport implements ToCollection, WithHeadingRow
{
    protected $errors = [];
    protected $successCount = 0;
    protected $failCount = 0;
    protected $houseCount = 0;
    protected $residentCount = 0;
    
    // Tracking state untuk anggota keluarga
    protected $currentRw = null;
    protected $currentRt = null;
    protected $currentNoRumah = null;

    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        try {
            // Get status mappings
            $statusAktif = ResidentStatus::where('code', 'AKTIF')->first();
            $statusPindah = ResidentStatus::where('code', 'PINDAH')->first();
            $statusMeninggal = ResidentStatus::where('code', 'MENINGGAL')->first();

            $currentFamily = null;
            $currentHouse = null;

            foreach ($rows as $index => $row) {
                try {
                    $rowNumber = $index + 2; // +2 karena heading row + 1-based index

                    // Get values (allow empty untuk beberapa field)
                    $noRw = $this->getValue($row, 'no_rw', 'NO RW', true);
                    $noRt = $this->getValue($row, 'no_rt', 'NO RT', true);
                    $noRumah = $this->getValue($row, 'no_rumah', 'NO RUMAH', true);
                    
                    // JENIS RUMAH - default RUMAH_TINGGAL jika kosong
                    $jenisRumahRaw = $this->getValue($row, 'jenis_rumah', 'JENIS RUMAH', true);
                    $jenisRumah = strtoupper(trim($jenisRumahRaw ?? ''));
                    
                    // Handle variasi penulisan JENIS RUMAH
                    if (stripos($jenisRumah, 'FASILITAS') !== false || stripos($jenisRumah, 'FASILITAS_UMUM') !== false) {
                        $jenisRumah = 'FASILITAS_UMUM';
                    } elseif (stripos($jenisRumah, 'WARUNG') !== false || stripos($jenisRumah, 'TOKO') !== false || stripos($jenisRumah, 'USAHA') !== false || stripos($jenisRumah, 'WARUNG_TOKO_USAHA') !== false) {
                        $jenisRumah = 'WARUNG_TOKO_USAHA';
                    } elseif (stripos($jenisRumah, 'KONTRAKAN') !== false) {
                        $jenisRumah = 'KONTRAKAN';
                    } elseif (empty($jenisRumah)) {
                        $jenisRumah = 'RUMAH_TINGGAL'; // Default jika kosong
                    }
                    
                    $namaKeluarga = $this->getValue($row, 'nama_keluarga', 'NAMA KELUARGA', true);
                    $noKk = $this->getValue($row, 'no_kk', 'NO KK', true);
                    
                    // Coba berbagai variasi key untuk NIK
                    $noNik = null;
                    $nikKeys = ['no_nik', 'no.nik', 'no nik', 'nonik', 'nik'];
                    foreach ($nikKeys as $nikKey) {
                        $noNik = $this->getValue($row, $nikKey, 'NO.NIK', true);
                        if (!empty($noNik)) {
                            break;
                        }
                    }
                    
                    // Coba berbagai variasi key untuk TEMPAT/TGL/LAHIR
                    $tempatTglLahir = null;
                    $ttlKeys = ['tempat_tgl_lahir', 'tempat_tgllahir', 'tempat /tgl/lahir', 'tempat/tgl/lahir', 'tempat_tgl lahir', 'tempat tgl lahir'];
                    foreach ($ttlKeys as $ttlKey) {
                        $tempatTglLahir = $this->getValue($row, $ttlKey, 'TEMPAT /TGL/LAHIR', true);
                        if (!empty($tempatTglLahir)) {
                            break;
                        }
                    }
                    $jenisKelamin = $this->getValue($row, 'jenis_kelamin', 'JENIS KELAMIN', true);
                    $status = $this->getValue($row, 'status', 'STATUS', true);

                    // Parse nama keluarga - cek apakah kepala keluarga (ada *)
                    $isKepalaKeluarga = !empty($namaKeluarga) && str_starts_with(trim($namaKeluarga), '*');

                    // Handle NO RW, NO RT, NO RUMAH
                    // Untuk kepala keluarga: wajib diisi
                    // Untuk anggota keluarga: bisa kosong (menggunakan dari kepala keluarga)
                    if ($isKepalaKeluarga) {
                        // Kepala keluarga - semua field wajib
                        if (empty($noRw)) {
                            throw new \Exception("Kepala keluarga harus memiliki NO RW");
                        }
                        if (empty($noRt)) {
                            throw new \Exception("Kepala keluarga harus memiliki NO RT");
                        }
                        if (empty($noRumah)) {
                            throw new \Exception("Kepala keluarga harus memiliki NO RUMAH");
                        }
                        // Simpan untuk anggota keluarga berikutnya
                        $this->currentRw = $noRw;
                        $this->currentRt = $noRt;
                        $this->currentNoRumah = $noRumah;
                    } else {
                        // Anggota keluarga - gunakan dari kepala keluarga jika kosong
                        if (empty($noRw) && $this->currentRw) {
                            $noRw = $this->currentRw;
                        }
                        if (empty($noRt) && $this->currentRt) {
                            $noRt = $this->currentRt;
                        }
                        if (empty($noRumah) && $this->currentNoRumah) {
                            $noRumah = $this->currentNoRumah;
                        }
                        
                        // Validasi: jika masih kosong, berarti belum ada kepala keluarga sebelumnya
                        if (empty($noRw) || empty($noRt) || empty($noRumah)) {
                            throw new \Exception("Baris {$rowNumber}: Tidak ada kepala keluarga sebelumnya. Pastikan kepala keluarga (dengan *) diisi terlebih dahulu dengan NO RW, NO RT, dan NO RUMAH.");
                        }
                    }

                    // Validate jenis_rumah
                    $validJenisRumah = ['RUMAH_TINGGAL', 'KONTRAKAN', 'WARUNG_TOKO_USAHA', 'FASILITAS_UMUM'];
                    if (!in_array($jenisRumah, $validJenisRumah)) {
                        $jenisRumah = 'RUMAH_TINGGAL'; // Default jika tidak valid
                    }

                    // Find or create RW
                    $rw = Rws::where('nomor_rw', $noRw)->first();
                    if (!$rw) {
                        throw new \Exception("RW dengan nomor {$noRw} tidak ditemukan. Silakan buat RW terlebih dahulu.");
                    }

                    // Find or create RT (auto-create jika belum ada)
                    $rt = Rts::where('rw_id', $rw->id)
                        ->where('nomor_rt', $noRt)
                        ->first();
                    if (!$rt) {
                        // Auto-create RT jika belum ada
                        $rt = Rts::create([
                            'rw_id' => $rw->id,
                            'nomor_rt' => $noRt,
                            'created_by' => auth()->id(),
                            'updated_by' => auth()->id(),
                        ]);
                    }

                    // Handle non-RUMAH_TINGGAL (FASILITAS_UMUM, WARUNG_TOKO_USAHA, KONTRAKAN)
                    if ($jenisRumah !== 'RUMAH_TINGGAL') {
                        // Find or create House dengan jenis_rumah yang sesuai
                        $house = Houses::where('rt_id', $rt->id)
                            ->where('nomor_rumah', $noRumah)
                            ->first();

                        $houseData = [
                            'rt_id' => $rt->id,
                            'nomor_rumah' => $noRumah,
                            'jenis_rumah' => $jenisRumah,
                            'created_by' => auth()->id(),
                            'updated_by' => auth()->id(),
                        ];

                        if ($jenisRumah === 'FASILITAS_UMUM' && !empty($namaKeluarga) && $namaKeluarga !== '-') {
                            $houseData['nama_fasilitas'] = trim($namaKeluarga);
                        } elseif ($jenisRumah === 'WARUNG_TOKO_USAHA' && !empty($namaKeluarga) && $namaKeluarga !== '-') {
                            $houseData['nama_usaha'] = trim($namaKeluarga);
                        } elseif ($jenisRumah === 'KONTRAKAN' && !empty($namaKeluarga) && $namaKeluarga !== '-') {
                            $houseData['nama_pemilik'] = trim($namaKeluarga);
                        }

                        if ($house) {
                            $house->update($houseData);
                        } else {
                            $house = Houses::create($houseData);
                        }

                        $this->houseCount++;
                        $currentFamily = null; // Reset untuk jenis rumah non-tinggal
                        $this->currentRw = null; // Reset tracking
                        $this->currentRt = null;
                        $this->currentNoRumah = null;
                        continue; // Skip proses family/resident
                    }

                    // Handle RUMAH_TINGGAL - proses normal
                    // Find or create House
                    $house = Houses::where('rt_id', $rt->id)
                        ->where('nomor_rumah', $noRumah)
                        ->first();
                    if (!$house) {
                        $house = Houses::create([
                            'rt_id' => $rt->id,
                            'nomor_rumah' => $noRumah,
                            'jenis_rumah' => 'RUMAH_TINGGAL',
                            'created_by' => auth()->id(),
                            'updated_by' => auth()->id(),
                        ]);
                    } else {
                        // Update jenis_rumah jika belum set
                        if ($house->jenis_rumah !== 'RUMAH_TINGGAL') {
                            $house->update(['jenis_rumah' => 'RUMAH_TINGGAL']);
                        }
                    }

                    // Jika tidak ada NAMA KELUARGA, NO KK, NO.NIK -> skip (baris kosong)
                    if (empty($namaKeluarga) && empty($noKk) && empty($noNik)) {
                        continue;
                    }

                    // Parse nama keluarga (sudah dicek di atas, tapi perlu untuk nama lengkap)
                    $namaLengkap = $isKepalaKeluarga ? ltrim(trim($namaKeluarga), '*') : trim($namaKeluarga);

                    // Normalize NIK dan NO KK SEBELUM validasi (handle scientific notation)
                    // Ini penting karena NIK mungkin dalam format scientific notation
                    // Cek dulu apakah NIK ada (bisa dalam berbagai format)
                    $noNikRaw = $noNik;
                    $noKkRaw = $noKk;
                    
                    // Normalize NIK - handle berbagai format termasuk scientific notation
                    $noNikNormalized = null;
                    if (!empty($noNik) && $noNik !== '-' && trim($noNik) !== '') {
                        $noNikNormalized = $this->normalizeNikKk($noNik);
                    }
                    
                    // Normalize NO KK
                    $noKkNormalized = null;
                    if (!empty($noKk) && $noKk !== '-' && trim($noKk) !== '') {
                        $noKkNormalized = $this->normalizeNikKk($noKk);
                    }

                    // Validasi: kepala keluarga harus ada NO KK
                    if ($isKepalaKeluarga && empty($noKkNormalized)) {
                        throw new \Exception("Kepala keluarga harus memiliki NO KK");
                    }

                    // Validasi: harus ada NIK untuk resident (setelah normalize)
                    if (empty($noNikNormalized)) {
                        // Debug info untuk troubleshooting
                        $rowKeys = $row->keys()->toArray();
                        $debugKeys = implode(', ', array_slice($rowKeys, 0, 20)); // Limit untuk tidak terlalu panjang
                        throw new \Exception("NO.NIK tidak boleh kosong untuk resident. NIK raw: " . var_export($noNikRaw, true) . ". Available keys: " . $debugKeys);
                    }

                    // Validasi: harus ada tempat/tgl lahir untuk resident
                    if (empty($tempatTglLahir) || $tempatTglLahir === '-') {
                        throw new \Exception("TEMPAT /TGL/LAHIR tidak boleh kosong untuk resident");
                    }

                    // Validasi: harus ada jenis kelamin untuk resident
                    if (empty($jenisKelamin) || $jenisKelamin === '-') {
                        throw new \Exception("JENIS KELAMIN tidak boleh kosong untuk resident");
                    }

                    // Gunakan NIK dan NO KK yang sudah dinormalize
                    $noNik = $noNikNormalized;
                    $noKk = $noKkNormalized;

                    // Parse tempat/tgl lahir
                    $parsedBirth = $this->parseTempatTglLahir($tempatTglLahir);

                    // Determine status
                    $statusCode = strtoupper(trim($status ?? ''));
                    $statusId = $statusAktif->id; // default AKTIF
                    if ($statusCode === 'MENINGGAL' || $statusCode === 'MENINGGAL DUNIA') {
                        $statusId = $statusMeninggal->id;
                    } elseif ($statusCode === 'PINDAH' || $statusCode === 'PINDAH SEMUA') {
                        $statusId = $statusPindah->id;
                    }

                    // Validate NIK
                    if (strlen($noNik) < 16) {
                        throw new \Exception("NIK harus 16 digit. NIK yang diinput: {$noNik}");
                    }

                    // Check if resident already exists
                    $existingResident = Residents::where('nik', $noNik)->first();
                    if ($existingResident) {
                        throw new \Exception("Resident dengan NIK {$noNik} sudah ada");
                    }

                    // Handle keluarga
                    if ($isKepalaKeluarga && !empty($noKk)) {
                        // Ini kepala keluarga, buat/update family
                        $family = Families::where('no_kk', $noKk)->first();
                        if (!$family) {
                            $family = Families::create([
                                'house_id' => $house->id,
                                'no_kk' => $noKk,
                                'status' => 'AKTIF',
                                'created_by' => auth()->id(),
                                'updated_by' => auth()->id(),
                            ]);
                        } else {
                            // Update house_id jika berbeda
                            if ($family->house_id != $house->id) {
                                $family->update(['house_id' => $house->id]);
                            }
                        }
                        $currentFamily = $family;
                        // Update tracking state untuk anggota keluarga berikutnya
                        $this->currentRw = $noRw;
                        $this->currentRt = $noRt;
                        $this->currentNoRumah = $noRumah;
                    } elseif ($currentFamily) {
                        // Anggota keluarga, gunakan currentFamily
                        $family = $currentFamily;
                    } else {
                        throw new \Exception("Baris {$rowNumber}: Tidak ada kepala keluarga sebelumnya. Pastikan kepala keluarga (dengan *) diisi terlebih dahulu.");
                    }

                    // Create resident
                    $resident = Residents::create([
                        'family_id' => $family->id,
                        'nik' => $noNik,
                        'nama' => $namaLengkap,
                        'tempat_lahir' => $parsedBirth['tempat'],
                        'tanggal_lahir' => $parsedBirth['tanggal'],
                        'jenis_kelamin' => strtoupper($jenisKelamin) === 'P' ? 'P' : 'L',
                        'status_id' => $statusId,
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);

                    // Jika kepala keluarga, update family->kepala_keluarga_id
                    if ($isKepalaKeluarga && $family->kepala_keluarga_id === null) {
                        $family->update(['kepala_keluarga_id' => $resident->id]);
                    }

                    $this->residentCount++;
                    $this->successCount++;
                } catch (\Exception $e) {
                    $this->failCount++;
                    $this->errors[] = "Baris {$rowNumber}: " . $e->getMessage();
                    Log::error("Import Excel Error Row {$rowNumber}: " . $e->getMessage());
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Normalize NIK/KK dari berbagai format (scientific notation, dll)
     */
    protected function normalizeNikKk($value)
    {
        if ($value === null || $value === '' || $value === '-') {
            return null;
        }

        // Convert to string first - handle berbagai tipe data
        if (is_numeric($value)) {
            // Jika sudah numeric, handle scientific notation
            if (is_float($value) || (is_numeric($value) && $value >= 1e15)) {
                // Large number yang mungkin scientific notation
                // Gunakan sprintf untuk format tanpa scientific notation
                $value = sprintf('%.0f', $value);
            } else {
                $value = (string) $value;
            }
        } else {
            $value = trim((string) $value);
        }
        
        // Handle scientific notation string (e.g., "3.20116E+15", "3,20116E+15", "3.20116e+15")
        if (stripos($value, 'e') !== false) {
            // Replace comma dengan dot untuk scientific notation dengan comma
            $value = str_replace(',', '.', $value);
            // Convert scientific notation to number
            $floatValue = (float) $value;
            // Convert back to string without scientific notation (no decimal, no comma)
            $value = sprintf('%.0f', $floatValue);
        }

        // Remove any non-digit characters (comma, dot, space, dll)
        $value = preg_replace('/[^0-9]/', '', $value);

        // Return null jika hasilnya kosong atau terlalu pendek (bukan NIK valid)
        if (empty($value) || strlen($value) < 10) {
            return null;
        }

        return $value;
    }

    protected function getValue($row, $key, $label, $allowEmpty = false)
    {
        $value = null;
        
        // Convert row to array untuk memudahkan pencarian
        $rowArray = $row->toArray();
        
        // Try different key variations
        $keys = [
            strtolower(str_replace([' ', '.', '/'], ['_', '_', '_'], $key)),
            strtolower(str_replace([' ', '.', '/'], ['', '', ''], $key)),
            strtolower(str_replace([' ', '.'], ['_', ''], $key)),
            strtolower(str_replace(' ', '_', $key)),
            strtolower(str_replace(' ', '', $key)),
            strtolower(str_replace('_', '', $key)), // nonik dari no_nik
            strtolower($key),
            $key,
            // Tambahan untuk NIK: coba dengan titik dan tanpa titik
            str_replace('.', '', strtolower($key)),
            str_replace('no_nik', 'no.nik', strtolower($key)),
            str_replace('no.nik', 'no_nik', strtolower($key)),
            str_replace('no_nik', 'nonik', strtolower($key)), // nonik
            // Tambahan untuk TEMPAT/TGL/LAHIR
            str_replace('tempat_tgl_lahir', 'tempat_tgllahir', strtolower($key)),
            str_replace('tempat_tgllahir', 'tempat_tgl_lahir', strtolower($key)),
        ];

        // Cek exact match dulu
        foreach ($keys as $k) {
            if (isset($rowArray[$k])) {
                $value = $rowArray[$k];
                break;
            }
        }
        
        // Jika belum ketemu, cek case-insensitive match
        if ($value === null) {
            foreach ($rowArray as $rowKey => $rowValue) {
                $rowKeyLower = strtolower(trim((string)$rowKey));
                foreach ($keys as $k) {
                    if ($rowKeyLower === strtolower(trim((string)$k))) {
                        $value = $rowValue;
                        break 2; // Break dari kedua loop
                    }
                }
            }
        }

        // Handle empty values
        if ($value === null || $value === '' || (is_string($value) && trim($value) === '')) {
            if (!$allowEmpty) {
                throw new \Exception("Kolom {$label} tidak boleh kosong");
            }
            return null;
        }

        // Convert to string untuk handle scientific notation dan numeric values
        if (is_numeric($value)) {
            $value = (string) $value;
        }

        return $value;
    }

    protected function parseTempatTglLahir($value)
    {
        $value = trim($value);
        
        if (empty($value) || $value === '-') {
            throw new \Exception("TEMPAT /TGL/LAHIR tidak boleh kosong");
        }
        
        // Format: "JAKARTA, 16 NOPEMBER 1977" atau "BOGOR, 1 APRIL 1986"
        $parts = explode(',', $value, 2);
        
        $tempat = trim($parts[0]);
        $tanggal = isset($parts[1]) ? trim($parts[1]) : '';

        // Parse tanggal
        $bulanMap = [
            'JANUARI' => 1, 'FEBRUARI' => 2, 'MARET' => 3, 'MEI' => 4, 'APRIL' => 4, 'JUNI' => 6,
            'JULI' => 7, 'AGUSTUS' => 8, 'SEPTEMBER' => 9, 'OKTOBER' => 10, 'NOPEMBER' => 11, 'DESEMBER' => 12,
        ];

        $tanggalLahir = null;
        if (!empty($tanggal)) {
            // Extract tanggal, bulan, tahun
            $tanggalParts = explode(' ', trim($tanggal));
            if (count($tanggalParts) >= 3) {
                $hari = (int)$tanggalParts[0];
                $bulanStr = strtoupper($tanggalParts[1]);
                $tahun = (int)$tanggalParts[2];
                
                $bulan = $bulanMap[$bulanStr] ?? null;
                if ($bulan && $hari > 0 && $hari <= 31 && $tahun > 1900 && $tahun < 2100) {
                    try {
                        $tanggalLahir = Carbon::create($tahun, $bulan, $hari)->format('Y-m-d');
                    } catch (\Exception $e) {
                        throw new \Exception("Tanggal tidak valid: {$tanggal}");
                    }
                }
            }
        }

        if (!$tanggalLahir) {
            throw new \Exception("Format tanggal lahir tidak valid. Format: TEMPAT, TANGGAL BULAN TAHUN (contoh: JAKARTA, 16 NOPEMBER 1977). Nilai: {$value}");
        }

        return [
            'tempat' => $tempat,
            'tanggal' => $tanggalLahir,
        ];
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getSuccessCount()
    {
        return $this->successCount;
    }

    public function getFailCount()
    {
        return $this->failCount;
    }

    public function getHouseCount()
    {
        return $this->houseCount;
    }
}

