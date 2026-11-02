<?php

namespace Tests\Http\Controllers\MedicalRecordController;

use App\Http\Controllers\MedicalRecordController;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class ImportTest extends TestCase
{
    public function rota(): string
    {
        return route('medical-record.import');
    }

    #[Test, TestDox('Deve retornar a rota correta')]
    public function rotaCorreta(): void
    {
        $this->assertEquals(
            $this->rota(),
            URL::action([MedicalRecordController::class, 'importMedicalRecord'])
        );
    }

    #[Test, TestDox('Deve importar um DOCX retornando apenas o conteúdo do corpo HTML')]
    public function sucesso(): void
    {
        $this->login();
        $path = tempnam(sys_get_temp_dir(), 'record_test_');

        try {
            $word = new PhpWord;
            $word->addSection()->addText('Evolução clínica de João');
            IOFactory::createWriter($word, 'Word2007')->save($path);

            // Controla o MIME da fixture sem depender do libmagic do container.
            $file = UploadedFile::fake()->createWithContent('record.docx', file_get_contents($path));

            $response = $this->postJson($this->rota(), ['file' => $file])
                ->assertOk();

            $this->assertStringContainsString('Evolução clínica de João', html_entity_decode($response->json('data.content')));
            $this->assertStringNotContainsString('<body', $response->json('data.content'));
            $this->assertStringNotContainsString('<html', $response->json('data.content'));
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    #[
        Test,
        TestDox('Deve retornar erro para parâmetros inválidos'),
        DataProvider('parametrosInvalidosDataProvider')
    ]
    public function erroParametrosInvalidos(array $parametro): void
    {
        $this->login();
        $parametro = $this->mapRecursive($parametro, []);

        $this->postJson($this->rota(), $parametro)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public static function parametrosInvalidosDataProvider(): array
    {
        return [
            'arquivo ausente' => [
                ['file' => null],
            ],
            'formato inválido' => [
                ['file' => fn () => UploadedFile::fake()->create('record.pdf', 10, 'application/pdf')],
            ],
            'arquivo acima de 10 MB' => [
                ['file' => fn () => UploadedFile::fake()->create('record.docx', 10241, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')],
            ],
        ];
    }

    #[Test, TestDox('Não deve importar prontuário sem estar logado')]
    public function erroNaoLogado(): void
    {
        $this->postJson($this->rota())
            ->assertUnauthorized();
    }
}
