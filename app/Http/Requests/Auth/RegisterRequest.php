<?php

namespace App\Http\Requests\Auth;

use App\Domain\Identity\Departments;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $email = strtolower(trim((string) $this->input('email', '')));

        $this->merge([
            'name' => trim((string) $this->input('name', '')),
            'email' => $email,
            'device_fingerprint' => $this->header('X-Device-Fingerprint'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'string', 'max:160', 'email:rfc', 'unique:users,email'],
            'nick' => ['required', 'string', 'min:3', 'max:20', 'unique:users,nick', 'regex:/^[a-zA-Z0-9_]+$/'],
            'department' => ['required', 'string', Rule::in(Departments::all())],
            'birth_year' => ['required', 'integer', 'min:1900', 'max:'.(now()->year - 18)],
            'password' => ['required', 'string', 'min:8', 'max:80', 'confirmed'],
            'device_fingerprint' => ['required', 'string', 'min:8', 'max:128'],
            'consent_terms' => ['required', 'accepted'],
            'consent_metrics' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Escribe tu nombre.',
            'email.unique' => 'Ese correo ya tiene una cuenta.',
            'email.email' => 'El correo no es válido.',
            'nick.unique' => 'Ese nick ya está en uso.',
            'nick.regex' => 'El nick solo puede tener letras, números y guion bajo.',
            'department.in' => 'Elige un departamento de la lista.',
            'birth_year.max' => 'Tienes que tener al menos 18 años para crear una cuenta.',
            'password.min' => 'La contraseña tiene al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'consent_terms.accepted' => 'Tienes que aceptar los términos.',
            'device_fingerprint.required' => 'Falta la identificación del dispositivo.',
        ];
    }
}
