<?php

namespace App\Http\Requests\V1\Bookmarks;

use App\Enums\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

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
            'order_by' => ['filled', 'string', new Enum(Order::class)],
            'search' => ['filled', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'order_by.Illuminate\Validation\Rules\Enum' => 'The order_by value is invalid. Valid values are `newest` and `oldest`.',
        ];
    }
}
