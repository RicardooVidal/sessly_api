<?php

namespace App\Data\Admin;

use App\Models\Company;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Support\Validation\ValidationContext;

#[MapName(SnakeCaseMapper::class)]
class UpdateCompanyData extends Data
{
    public function __construct(
        public string $name = '',
        public string $email = '',
        public string $document = '',
        public string $until = '',
        public bool $active = true,
        public ?string $contract = null,
        public ?string $userName = null,
        public ?string $userEmail = null,
        public ?string $userPassword = null,
        public ?string $userPasswordConfirmation = null,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $company = request()->route('company');

        if (is_string($company)) {
            $company = Company::query()->where('uuid', $company)->first();
        }

        if (! $company instanceof Company) {
            $uuid = data_get($context->payload, 'uuid');
            $company = $uuid ? Company::query()->where('uuid', $uuid)->first() : null;
        }

        $userId = $company?->users()->where('id', '!=', 1)->value('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'document' => [
                'required',
                'string',
                'max:20',
                Rule::unique('companies', 'document')->ignore($company?->id),
            ],
            'contract' => ['nullable', 'string', 'max:255'],
            'until' => ['required', 'date_format:Y-m-d'],
            'active' => ['required', 'boolean'],
            'user_name' => ['nullable', 'string', 'max:255', 'required_with:user_email,user_password'],
            'user_email' => [
                'nullable',
                'email',
                'max:255',
                'required_with:user_name,user_password',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'user_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public static function messages(...$args): array
    {
        return [
            'name.required' => 'O nome da empresa é obrigatório.',
            'email.required' => 'O e-mail da empresa é obrigatório.',
            'email.email' => 'Informe um e-mail válido para a empresa.',
            'document.required' => 'O documento da empresa é obrigatório.',
            'document.unique' => 'Já existe uma empresa com este documento.',
            'until.required' => 'A data de validade é obrigatória.',
            'until.date_format' => 'A data de validade deve estar no formato AAAA-MM-DD.',
            'user_name.required_with' => 'O nome do usuário é obrigatório quando os dados do usuário são informados.',
            'user_email.email' => 'Informe um e-mail válido para o usuário.',
            'user_email.required_with' => 'O e-mail do usuário é obrigatório quando os dados do usuário são informados.',
            'user_email.unique' => 'Já existe um usuário com este e-mail.',
            'user_password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'user_password.confirmed' => 'A confirmação da senha não confere.',
        ];
    }
}
