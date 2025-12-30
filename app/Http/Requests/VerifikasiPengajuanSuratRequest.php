<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifikasiPengajuanSuratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $status = $this->status;

        $rules = [
            'id' => 'required|exists:pengajuan_surat,id',
            'status' => 'required|in:disetujui,ditolak',
        ];

        if ($status === 'disetujui') {
            $rules['tanda_tangan_type'] = 'required|in:digital,foto';
            $rules['use_existing_ttd'] = 'required|in:yes,no';
            
            // Jika digital, harus ada tanda_tangan_digital (bisa string base64 atau file)
            if ($this->tanda_tangan_type === 'digital') {
                // Check if it's using existing TTD or new one
                if ($this->use_existing_ttd === 'yes') {
                    // Using existing, tanda_tangan_digital optional (can be empty, backend will use existing)
                } else {
                    // New TTD required - can be file (blob) or base64 string
                    // Accept both file and string (base64)
                    if ($this->hasFile('tanda_tangan_digital')) {
                        $rules['tanda_tangan_digital'] = 'required|file|image|max:2048';
                    } else {
                        $rules['tanda_tangan_digital'] = 'required|string';
                    }
                }
            }
            
            // Jika foto, harus ada foto_tanda_tangan (file upload)
            if ($this->tanda_tangan_type === 'foto') {
                // Check if it's using existing TTD or new one
                if ($this->use_existing_ttd === 'yes') {
                    // Using existing, foto_tanda_tangan optional
                } else {
                    // New foto TTD required
                    $rules['foto_tanda_tangan'] = 'required|file|image|max:2048';
                }
            }
        } elseif ($status === 'ditolak') {
            $rules['alasan_penolakan'] = 'required|string|min:10';
        }

        return $rules;
    }
}

