<?php

namespace App\Http\Requests\Admin;

use App\Domain\Identity\Departments;
use App\Domain\Identity\Municipalities;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FilterStaffDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $search = trim((string) $this->query('q', ''));
        $department = trim((string) $this->query('department', ''));
        $municipality = trim((string) $this->query('municipality', ''));

        $this->merge([
            'q' => $search === '' ? null : $search,
            'department' => $department === '' ? null : $department,
            'municipality' => $municipality === '' ? null : $municipality,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:80'],
            'department' => ['nullable', 'string', Rule::in(Departments::all())],
            'municipality' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $department = $this->input('department');
            $municipality = $this->input('municipality');
            if (! is_string($municipality) || $municipality === '') {
                return;
            }

            if (! is_string($department) || ! Municipalities::belongs($department, $municipality)) {
                $validator->errors()->add('municipality', 'Elige un municipio de ese departamento.');
            }
        });
    }
}
