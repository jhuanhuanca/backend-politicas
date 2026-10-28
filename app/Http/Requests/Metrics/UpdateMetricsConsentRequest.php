<?php

namespace App\Http\Requests\Metrics;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMetricsConsentRequest extends FormRequest
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
            'consent_metrics' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'consent_metrics.required' => 'Indica si aceptas el perfil de métricas.',
            'consent_metrics.boolean' => 'Indica si aceptas el perfil de métricas.',
        ];
    }
}
