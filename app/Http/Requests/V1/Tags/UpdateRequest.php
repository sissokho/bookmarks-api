<?php

namespace App\Http\Requests\V1\Tags;

use App\Models\Tag;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read Tag $tag
 */
class UpdateRequest extends FormRequest
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
            'name' => [
                'required',
                'max:255',
                Rule::unique('tags', 'name')
                    ->where('user_id', Auth::id())
                    ->ignore($this->tag->id),
            ],
        ];
    }
}
