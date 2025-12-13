<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RtsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'rw_id'     => 'required|exists:rws,id',
            'nomor_rt'  => 'required|string|max:10',
            'keterangan' => 'nullable|string',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required';
            $rules['nomor_rt'] = [
                'required',
                'string',
                'max:10',
                Rule::unique('rts', 'nomor_rt')->where('rw_id', $this->rw_id)->ignore($this->id),
            ];
        } else {
            $rules['nomor_rt'] = [
                'required',
                'string',
                'max:10',
                Rule::unique('rts', 'nomor_rt')->where('rw_id', $this->rw_id),
            ];
        }

        return $rules;
    }
}

