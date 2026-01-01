<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KategoriProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'nama' => 'required|string|max:255',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama kategori proposal wajib diisi.',
            'nama.string' => 'Nama kategori proposal harus berupa teks.',
            'nama.max' => 'Nama kategori proposal maksimal 255 karakter.',
        ];
    }
}

