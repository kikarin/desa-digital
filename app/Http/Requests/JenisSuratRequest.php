<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JenisSuratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'nama' => 'required|string|max:100',
            'kode' => 'required|string|max:50',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required';
            $rules['kode'] = [
                'required',
                'string',
                'max:50',
                Rule::unique('jenis_surat', 'kode')->ignore($this->id),
            ];
        } else {
            $rules['kode'] = [
                'required',
                'string',
                'max:50',
                Rule::unique('jenis_surat', 'kode'),
            ];
        }

        return $rules;
    }
}

