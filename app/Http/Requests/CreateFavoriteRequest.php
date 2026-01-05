<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateFavoriteRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user();
    }

    public function rules()
    {
        return [];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $favoritable = $this->route('user');

            if (!is_null($favoritable) && $favoritable instanceof User && $favoritable->is($this->user())) {
                $validator->errors()->add('favoritable', 'You cannot favorite yourself.');
            }
        });
    }
}
