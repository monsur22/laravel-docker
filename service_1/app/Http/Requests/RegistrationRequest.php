<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class RegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => 'required|string|between:2,100',
            'email' => ['required', 'string', 'email', 'max:100', Rule::unique('users')->where(function ($q) {
                $q->whereNotNull('email_verified_at');
            }) ],
            'password' => 'required|string|confirmed|min:5',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'Validation errors',
            'data'      => $validator->errors()
        ]));

    }
    public function messages()
    {
        return [
            'name.required' => 'This field is required.',
            'name.string' => 'This accept string',
            'email.required' => 'This field is required.',
            'email.unique' => 'This email already exists.',
            'password.required' => 'This field is required.',
            'password.confirmed' => 'Password and confirm password must be same',
        ];
    }
}
