<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BeritaPengumumanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'tipe' => 'required|in:berita,event',
            'title' => 'required|string|max:255',
            'tanggal' => 'required|date',
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
            'tipe.required' => 'Tipe harus dipilih.',
            'tipe.in' => 'Tipe harus berita atau event.',
            'title.required' => 'Title wajib diisi.',
            'title.max' => 'Title maksimal 255 karakter.',
            'tanggal.required' => 'Tanggal wajib diisi.',
            'tanggal.date' => 'Tanggal harus format tanggal yang valid.',
            'foto.image' => 'File harus berupa gambar.',
            'foto.max' => 'Ukuran file maksimal 2MB.',
        ];
    }
}

