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
        } elseif ($status === 'ditolak') {
            $rules['alasan_penolakan'] = 'required|string|min:10';
        }

        return $rules;
    }
}

