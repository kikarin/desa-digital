<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtributJenisSuratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'jenis_surat_id' => 'required|exists:jenis_surat,id',
            'nama_atribut' => 'required|string|max:100',
            'tipe_data' => 'required|in:text,number,date,select,boolean',
            'opsi_pilihan' => 'nullable|string',
            'is_required' => 'nullable|boolean',
            'nama_lampiran' => 'nullable|string|max:100',
            'minimal_file' => 'nullable|integer|min:0',
            'is_required_lampiran' => 'nullable|boolean',
            'urutan' => 'nullable|integer|min:0',
        ];

        // Jika tipe_data adalah select, opsi_pilihan wajib diisi
        if ($this->tipe_data === 'select') {
            $rules['opsi_pilihan'] = 'required|string';
        }

        // Jika ada nama_lampiran, minimal_file wajib diisi
        if ($this->nama_lampiran) {
            $rules['minimal_file'] = 'required|integer|min:1';
        }

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required';
        }

        return $rules;
    }
}

