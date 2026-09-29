<?php

namespace App\Data\Appointment;

use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class ImportMedicalRecordData extends Data
{
    public function __construct(
        public UploadedFile $file
    )
    {}

    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:docx',
                'max:10240'
            ]
        ];
    }

    public static function messages(...$args): array
    {
        return [
            'file.required' => 'O arquivo é obrigatório',
            'file.mimes' => 'O arquivo precisa ter um arquivo docx',
            'file.max' => 'O arquivo é muito grande para ser importado'
        ];;
    }
}
