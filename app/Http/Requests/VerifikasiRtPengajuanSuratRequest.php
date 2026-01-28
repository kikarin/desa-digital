<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifikasiRtPengajuanSuratRequest extends FormRequest
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
            'status' => 'required|in:diverifikasi_rt,ditolak',
            'rt_catatan' => 'nullable|string|max:1000',
        ];

        if ($status === 'ditolak') {
            $rules['rt_catatan'] = 'required|string|max:1000';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'id.required' => 'ID pengajuan surat wajib diisi.',
            'id.exists' => 'Pengajuan surat tidak ditemukan.',
            'status.required' => 'Status verifikasi wajib diisi.',
            'status.in' => 'Status verifikasi harus diverifikasi_rt atau ditolak.',
            'rt_catatan.required' => 'Catatan wajib diisi jika pengajuan ditolak.',
            'rt_catatan.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }
}
