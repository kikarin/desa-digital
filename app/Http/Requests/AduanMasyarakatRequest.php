<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AduanMasyarakatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'kategori_aduan_id' => 'required|exists:mst_kategori_aduan,id',
            'judul' => 'required|string|max:255',
            'detail_aduan' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'nama_lokasi' => 'nullable|string|max:255',
            'deskripsi_lokasi' => 'nullable|string',
            'jenis_aduan' => 'required|in:publik,private',
            'alasan_melaporkan' => 'nullable|string',
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:jpg,jpeg,png,mp4,mov,avi|max:10240', // Max 10MB
            'deleted_files' => 'nullable|array',
            'deleted_files.*' => 'exists:aduan_masyarakat_files,id',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'kategori_aduan_id.required' => 'Kategori aduan wajib diisi.',
            'kategori_aduan_id.exists' => 'Kategori aduan tidak valid.',
            'judul.required' => 'Judul wajib diisi.',
            'judul.max' => 'Judul maksimal 255 karakter.',
            'detail_aduan.required' => 'Detail aduan wajib diisi.',
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'latitude.between' => 'Latitude harus antara -90 dan 90.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'longitude.between' => 'Longitude harus antara -180 dan 180.',
            'jenis_aduan.required' => 'Jenis aduan wajib diisi.',
            'jenis_aduan.in' => 'Jenis aduan harus publik atau private.',
            'files.*.mimes' => 'File harus berupa gambar (jpg, jpeg, png) atau video (mp4, mov, avi).',
            'files.*.max' => 'Ukuran file maksimal 10MB.',
        ];
    }
}

