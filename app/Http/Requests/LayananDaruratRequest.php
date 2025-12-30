<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LayananDaruratRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('layanan-darurat');
        
        return [
            'kategori' => 'required|in:polsek,puskesmas,pemadam_kebakaran,rumah_sakit',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'title' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'nomor_whatsapp' => 'nullable|string|max:20',
        ];
    }
}

