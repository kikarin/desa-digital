<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifikasiRtAduanMasyarakatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $status = $this->status;

        $rules = [
            'id' => 'required|exists:aduan_masyarakat,id',
            'status' => 'required|in:diverifikasi_rt,dibatalkan',
            'rt_catatan' => 'nullable|string|max:1000',
        ];

        if ($status === 'dibatalkan') {
            $rules['rt_catatan'] = 'required|string|max:1000';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'id.required' => 'ID aduan masyarakat wajib diisi.',
            'id.exists' => 'Aduan masyarakat tidak ditemukan.',
            'status.required' => 'Status verifikasi wajib diisi.',
            'status.in' => 'Status verifikasi harus diverifikasi_rt atau dibatalkan.',
            'rt_catatan.required' => 'Catatan wajib diisi jika aduan dibatalkan.',
            'rt_catatan.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }
}
