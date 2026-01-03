<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PengajuanProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('pengajuan-proposal');
        
        $rules = [
            'kategori_proposal_id' => 'required|exists:mst_kategori_proposal,id',
            'resident_id' => 'required|exists:residents,id',
            'nomor_telepon_pengaju' => 'nullable|string|max:20',
            'nama_kegiatan' => 'required|string|max:255',
            'deskripsi_kegiatan' => 'required|string',
            'usulan_anggaran' => 'required|numeric|min:0',
            'file_pendukung' => 'nullable|array',
            'file_pendukung.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:10240',
            'existing_files' => 'nullable|array',
            'existing_files.*' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'nama_lokasi' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'thumbnail_foto_banner' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ];

        // Untuk create, tanda tangan digital wajib
        if (!$id) {
            $rules['tanda_tangan_digital'] = 'required|string';
        } else {
            // Untuk update, tanda tangan digital optional
            $rules['tanda_tangan_digital'] = 'nullable|string';
        }

        return $rules;
    }
}

