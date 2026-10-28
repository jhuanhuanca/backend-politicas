<?php

namespace App\Http\Requests\Moderation;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
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
            'target_type' => ['required', 'in:debate,argument,news'],
            'target_id' => ['required', 'integer'],
            'reason' => ['required', 'in:insulto,dato_falso,fuera_de_tema,spam,datos_personales'],
            'body' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target_type.in' => 'Elige un debate, un argumento o una noticia.',
            'reason.in' => 'Elige un motivo de la lista.',
            'reason.required' => 'Elige un motivo de la lista.',
            'body.max' => 'El detalle admite hasta 500 caracteres.',
        ];
    }
}
