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
        ];
    }
}

