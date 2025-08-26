<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name'=>'required',
            'last_name'=>'required',
            'gender'=>'required',
            'birth_date'=>'required|string',
            'phone_number'=>'required|unique:users,phone_number|digits:10|numeric',
            'email'=>'required|unique:users,email|email|max:255',
            'password'=>'required|confirmed|min:8',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|nullable',
            'fcm_token'=> 'string',
        ];
    }
}
