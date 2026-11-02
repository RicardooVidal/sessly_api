<?php

namespace App\Data\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class StoreCompanyData extends Data
{
    public function __construct(
        public string $company,
        public string $name,
        public string $email,
        public string $document,
        public string $until,
        public bool $active,
        public string $userName,
        public string $userEmail,
        public string $userPassword,
        public string $userPasswordConfirmation,
        public ?string $contract = null,
    ) {}

    public static function rules(): array
    {
        return [
            'company' => [
                'required',
                'string',
                'max:63',
                'regex:/^[a-z][a-z0-9_]*$/',
                'not_in:public,information_schema,pg_catalog,pg_toast',
                'not_regex:/^pg_/',
                Rule::unique('companies', 'company'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'document' => ['required', 'string', 'max:20', Rule::unique('companies', 'document')],
            'contract' => ['nullable', 'string', 'max:255'],
            'until' => ['required', 'date_format:Y-m-d'],
            'active' => ['required', 'boolean'],
            'user_name' => ['required', 'string', 'max:255'],
            'user_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'user_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public static function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $schema = $validator->getData()['company'] ?? null;

            if (! $schema || $validator->errors()->has('company')) {
                return;
            }

            if (DB::connection()->getDriverName() !== 'pgsql') {
                return;
            }

            $exists = DB::selectOne(
                'SELECT 1 FROM information_schema.schemata WHERE schema_name = ?',
                [$schema]
            );

            if ($exists) {
                $validator->errors()->add('company', 'Já existe um schema com este identificador.');
            }
        });
    }

    public static function messages(...$args): array
    {
        return [
            'company.required' => 'O identificador do schema é obrigatório.',
            'company.regex' => 'O schema deve iniciar com uma letra minúscula e conter apenas letras minúsculas, números e underscore.',
            'company.max' => 'O schema deve ter no máximo 63 caracteres.',
            'company.not_in' => 'Este identificador de schema é reservado.',
            'company.not_regex' => 'O schema não pode iniciar com "pg_".',
            'company.unique' => 'Já existe uma empresa com este identificador de schema.',
            'name.required' => 'O nome da empresa é obrigatório.',
            'email.required' => 'O e-mail da empresa é obrigatório.',
            'email.email' => 'Informe um e-mail válido para a empresa.',
            'document.required' => 'O documento da empresa é obrigatório.',
            'document.unique' => 'Já existe uma empresa com este documento.',
            'until.required' => 'A data de validade é obrigatória.',
            'until.date_format' => 'A data de validade deve estar no formato AAAA-MM-DD.',
            'user_name.required' => 'O nome do usuário é obrigatório.',
            'user_email.required' => 'O e-mail do usuário é obrigatório.',
            'user_email.email' => 'Informe um e-mail válido para o usuário.',
            'user_email.unique' => 'Já existe um usuário com este e-mail.',
            'user_password.required' => 'A senha do usuário é obrigatória.',
            'user_password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'user_password.confirmed' => 'A confirmação da senha não confere.',
        ];
    }
}
