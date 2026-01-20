<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResidentDeathsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resident_id'      => 'required|exists:residents,id',
            'tanggal_meninggal' => 'required|date',
            'keterangan'       => 'nullable|string',
            'surat_bukti_kematian' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240', // Max 10MB
        ];
    }
}

