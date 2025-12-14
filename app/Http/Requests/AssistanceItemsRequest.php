<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssistanceItemsRequest extends FormRequest
{
    /**
     * Cek apakah user punya akses untuk request ini
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rules validasi untuk form
     */
    public function rules(): array
    {
        $rules = [
            'nama_item' => 'required|string|max:100',
            'tipe'      => 'required|in:UANG,BARANG',
            'satuan'    => 'required|string|max:50',
        ];

        // Kalau update, wajib ada id
        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required';
        }

        return $rules;
    }
}

