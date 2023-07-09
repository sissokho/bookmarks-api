<?php

namespace App\Http\Requests\V1\Tags;

use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['filled', 'numeric', 'integer', 'min:1', 'max:100'],
            'page' => ['filled', 'numeric', 'integer', 'min:1'],
        ];
    }
}
