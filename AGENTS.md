# Instruções do projeto

## Testes PHP / PHPUnit

- Antes de criar ou alterar testes, leia testes existentes do mesmo módulo
  e siga os padrões do projeto.
- Todos os métodos de teste devem usar os atributos `#[Test]` e `#[TestDox]`.
- Importe os atributos:
  - `PHPUnit\Framework\Attributes\Test`
  - `PHPUnit\Framework\Attributes\TestDox`
- Não use o prefixo `test_` como substituto de `#[Test]`.
- Escreva as descrições de `#[TestDox]` em português.
- Siga o padrão existente de nomenclatura, organização, preparação de dados
  e assertions.
- Essas regras também se aplicam a testes adicionados em arquivos existentes.

## Verificação antes de concluir

- Confira se os testes novos e alterados seguem essas convenções.
- Execute os testes pertinentes à alteração.
Exemplo esperado:
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

#[Test]
#[TestDox('Nega acesso ao Telescope quando o usuário não está autenticado')]
public function deniesAccessWhenUserIsNotAuthenticated(): void
{
    $this->postJson('/api/telescope-access')
        ->assertForbidden()
        ->assertJson(['message' => 'Não autorizado']);
}
