<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FamiliesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'house_id' => 'required|exists:houses,id',
            'no_kk'    => 'required|string|size:16|regex:/^[0-9]+$/',
            'status'   => 'required|in:AKTIF,NON_AKTIF',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required';
            $rules['no_kk'] = [
                'required',
                'string',
                'size:16',
                'regex:/^[0-9]+$/',
                Rule::unique('families', 'no_kk')->ignore($this->id),
            ];
        } else {
            $rules['no_kk'] = [
                'required',
                'string',
                'size:16',
                'regex:/^[0-9]+$/',
                Rule::unique('families', 'no_kk'),
            ];
        }

        return $rules;
    }
}

