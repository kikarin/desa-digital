<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssistanceProgramItemsRequest extends FormRequest
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
            'assistance_program_id' => 'required|exists:assistance_programs,id',
            'assistance_item_id'    => 'required|exists:assistance_items,id',
            'jumlah'                => 'required|numeric|min:0',
        ];

        // Kalau update, wajib ada id
        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required';
            // Validasi unique: satu program tidak boleh punya item yang sama dua kali (abaikan soft deleted)
            $rules['assistance_item_id'] = [
                'required',
                'exists:assistance_items,id',
                Rule::unique('assistance_program_items', 'assistance_item_id')
                    ->where('assistance_program_id', $this->assistance_program_id)
                    ->whereNull('deleted_at')
                    ->ignore($this->id),
            ];
        } else {
            // Validasi unique untuk create (abaikan soft deleted)
            $rules['assistance_item_id'] = [
                'required',
                'exists:assistance_items,id',
                Rule::unique('assistance_program_items', 'assistance_item_id')
                    ->where('assistance_program_id', $this->assistance_program_id)
                    ->whereNull('deleted_at'),
            ];
        }

        return $rules;
    }

    /**
     * Custom messages untuk validasi
     */
    public function messages(): array
    {
        return [
            'assistance_item_id.unique' => 'Item ini sudah ditambahkan ke program ini.',
        ];
    }
}

