<?php

namespace App\Http\Requests\V1\Bookmarks;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:255'],
            'favorite' => ['required', 'boolean'],
            'tags' => ['array'],
            'tags.*' => ['string', 'max:255'],
        ];
    }
}
