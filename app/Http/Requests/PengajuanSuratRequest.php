<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PengajuanSuratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'jenis_surat_id' => 'required|exists:jenis_surat,id',
            'resident_id' => 'required|exists:residents,id',
            'tanggal_surat' => 'required|date',
            'atribut' => 'required|array',
        ];

        // Validasi atribut berdasarkan jenis surat
        $jenisSuratId = $this->jenis_surat_id;
        if ($jenisSuratId) {
            $jenisSurat = \App\Models\JenisSurat::with('atribut')->find($jenisSuratId);
            if ($jenisSurat) {
                foreach ($jenisSurat->atribut as $atribut) {
                    $key = "atribut.{$atribut->id}";
                    
                    // Validasi nilai atribut
                    if ($atribut->is_required) {
                        $rules[$key . '.nilai'] = 'required';
                    } else {
                        $rules[$key . '.nilai'] = 'nullable';
                    }

                    // Validasi tipe data
                    switch ($atribut->tipe_data) {
                        case 'number':
                            $rules[$key . '.nilai'] = ($rules[$key . '.nilai'] ?? 'nullable') . '|numeric';
                            break;
                        case 'date':
                            $rules[$key . '.nilai'] = ($rules[$key . '.nilai'] ?? 'nullable') . '|date';
                            break;
                        case 'boolean':
                            $rules[$key . '.nilai'] = ($rules[$key . '.nilai'] ?? 'nullable') . '|boolean';
                            break;
                        case 'select':
                            // Validasi opsi pilihan
                            $opsiArray = $atribut->opsi_pilihan_array;
                            if (!empty($opsiArray)) {
                                $rules[$key . '.nilai'] = ($rules[$key . '.nilai'] ?? 'nullable') . '|in:' . implode(',', $opsiArray);
                            }
                            break;
                    }

                    // Validasi lampiran jika ada
                    if ($atribut->nama_lampiran) {
                        $lampiranKey = $key . '.lampiran_files';
                        if ($atribut->is_required_lampiran) {
                            $rules[$lampiranKey] = 'required|array|min:' . $atribut->minimal_file;
                        } else {
                            $rules[$lampiranKey] = 'nullable|array';
                        }
                        
                        if ($atribut->minimal_file > 0) {
                            $rules[$lampiranKey] = ($rules[$lampiranKey] ?? 'nullable|array') . '|min:' . $atribut->minimal_file;
                        }
                    }
                }
            }
        }

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required';
        }

        return $rules;
    }
}

