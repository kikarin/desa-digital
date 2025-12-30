<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BankSampahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'nama_lokasi' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'title' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required';
            $rules['foto'] = 'nullable|file|image|max:2048'; // Max 2MB
        } else {
            $rules['foto'] = 'nullable|file|image|max:2048'; // Max 2MB
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'latitude.required' => 'Latitude wajib diisi.',
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'latitude.between' => 'Latitude harus antara -90 dan 90.',
            'longitude.required' => 'Longitude wajib diisi.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'longitude.between' => 'Longitude harus antara -180 dan 180.',
            'nama_lokasi.required' => 'Nama lokasi wajib diisi.',
            'nama_lokasi.max' => 'Nama lokasi maksimal 255 karakter.',
            'title.required' => 'Title wajib diisi.',
            'title.max' => 'Title maksimal 255 karakter.',
            'foto.image' => 'File harus berupa gambar.',
            'foto.max' => 'Ukuran file maksimal 2MB.',
        ];
    }
}

