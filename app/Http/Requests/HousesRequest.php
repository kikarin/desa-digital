<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HousesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxFileSizeInMB = (int) (config('media-library.max_file_size', 10240) / 1024);
        
        $rules = [
            'rt_id'       => 'required|exists:rts,id',
            'jenis_rumah' => 'required|in:RUMAH_TINGGAL,KONTRAKAN,WARUNG_TOKO_USAHA,FASILITAS_UMUM',
            'fotos'       => 'nullable|array',
            'fotos.*'     => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,bmp,svg|max:' . $maxFileSizeInMB,
            'deleted_media_ids' => 'nullable|array',
            'deleted_media_ids.*' => 'nullable|integer',
        ];

        $jenisRumah = $this->jenis_rumah;

        if ($jenisRumah === 'RUMAH_TINGGAL') {
            $rules['nomor_rumah'] = 'required|string|max:50';
            $rules['pemilik_id'] = 'nullable|exists:residents,id';
            $rules['nama_pemilik'] = 'nullable|string|max:255';
            $rules['keterangan'] = 'nullable|string';
        }

        if ($jenisRumah === 'KONTRAKAN') {
            $rules['nomor_rumah'] = 'required|string|max:50';
            $rules['nama_pemilik'] = 'nullable|string|max:100';
            $rules['status_hunian'] = 'required|in:DIHUNI,KOSONG';
            $rules['keterangan'] = 'nullable|string';
        }

        if ($jenisRumah === 'WARUNG_TOKO_USAHA') {
            $rules['nomor_rumah'] = 'required|string|max:50';
            $rules['nama_usaha'] = 'required|string|max:100';
            $rules['nama_pengelola'] = 'nullable|string|max:100';
            $rules['jenis_usaha'] = 'nullable|string|max:100';
            $rules['keterangan'] = 'nullable|string';
        }

        if ($jenisRumah === 'FASILITAS_UMUM') {
            $rules['nomor_rumah'] = 'nullable|string|max:50';
            $rules['nama_fasilitas'] = 'required|string|max:100';
            $rules['pengelola'] = 'required|in:DESA,RT,DINAS';
            $rules['keterangan'] = 'nullable|string';
        }

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required';
            if (isset($rules['nomor_rumah']) && $rules['nomor_rumah'] !== 'nullable') {
                $rules['nomor_rumah'] = [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('houses', 'nomor_rumah')->where('rt_id', $this->rt_id)->ignore($this->id),
                ];
            }
        } else {
            if (isset($rules['nomor_rumah']) && $rules['nomor_rumah'] !== 'nullable') {
                $rules['nomor_rumah'] = [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('houses', 'nomor_rumah')->where('rt_id', $this->rt_id),
                ];
            }
        }

        return $rules;
    }
}

