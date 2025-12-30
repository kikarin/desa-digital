<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminTandaTanganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'tanda_tangan_type' => 'required|in:digital,foto',
        ];

        // Jika digital, harus ada tanda_tangan_digital
        if ($this->tanda_tangan_type === 'digital') {
            $rules['tanda_tangan_digital'] = 'required|string';
        }

        // Jika foto, harus ada foto_tanda_tangan
        if ($this->tanda_tangan_type === 'foto') {
            $rules['foto_tanda_tangan'] = 'required|file|image|max:2048'; // Max 2MB
        }

        return $rules;
    }
}

