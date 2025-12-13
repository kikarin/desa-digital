<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\ResidentStatus;

class ResidentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'family_id'     => 'required|exists:families,id',
            'nik'           => 'required|string|size:16|regex:/^[0-9]+$/',
            'nama'          => 'required|string|max:100',
            'tempat_lahir'  => 'required|string|max:100',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:L,P',
            'status_id'     => 'required|exists:resident_statuses,id',
            'status_note'   => 'nullable|string',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required';
            $rules['nik'] = [
                'required',
                'string',
                'size:16',
                'regex:/^[0-9]+$/',
                Rule::unique('residents', 'nik')->ignore($this->id),
            ];
        } else {
            $rules['nik'] = [
                'required',
                'string',
                'size:16',
                'regex:/^[0-9]+$/',
                Rule::unique('residents', 'nik'),
            ];
        }

        $statusId = $this->status_id;
        if ($statusId) {
            $status = ResidentStatus::find($statusId);
            if ($status && $status->code === 'PINDAH') {
                $rules['jenis_pindah'] = 'nullable|in:INDIVIDU,KELUARGA';
                $rules['alamat_tujuan'] = 'nullable|string';
                $rules['desa'] = 'nullable|string|max:100';
                $rules['kecamatan'] = 'nullable|string|max:100';
                $rules['kabupaten'] = 'nullable|string|max:100';
                $rules['tanggal_pindah'] = 'nullable|date';
            }

            if ($status && $status->code === 'MENINGGAL') {
                $rules['tanggal_meninggal'] = 'required|date';
                $rules['keterangan'] = 'nullable|string';
            }
        }

        return $rules;
    }
}

