<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_admin' => ['boolean'],
        ];
    }

    /**
     * Hash the password before the validated data is passed to the controller,
     * so the controller never handles raw passwords.
     */
    protected function passedValidation(): void
    {
        $this->merge([
            'password' => bcrypt($this->password),
            'is_admin' => $this->boolean('is_admin', false),
        ]);
    }
}
