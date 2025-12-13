<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResidentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:100',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required';
            $rules['code'] = [
                'required',
                'string',
                'max:50',
                Rule::unique('resident_statuses', 'code')->ignore($this->id),
            ];
        } else {
            $rules['code'] = [
                'required',
                'string',
                'max:50',
                Rule::unique('resident_statuses', 'code'),
            ];
        }

        return $rules;
    }
}

