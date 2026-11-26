# Sessly API

## Build de produção

Na raiz da API:

```bash
docker build -f Dockerfile.prod -t sessly-api:local .
```

O build instala as dependências sem os pacotes de desenvolvimento. Depois de
copiar a aplicação, gera o autoload otimizado e executa o `package:discover`
por meio do script `post-autoload-dump` do Composer. Caches PHP gerados no
ambiente local não entram na imagem.

A extensão `pcntl` permite que o worker Laravel trate sinais de encerramento.
O Dockerfile local continua usando sua configuração de desenvolvimento.

## Deploy do Telescope

1. Configure o ambiente de produção, incluindo `APP_ENV=production`,
   `APP_DEBUG=false`, `APP_URL` com a URL pública do backend,
   `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis` e uma `APP_KEY` persistente.
2. Confirme as credenciais PostgreSQL e Redis. O Telescope usa a conexão
   `telescope`, separada da conexão dos tenants e fixa no schema `public`.
3. Reconstrua as imagens do backend e do frontend. A rota `/telescope` do
   frontend precisa fazer parte do novo build.
4. Execute `php artisan migrate --force` em um container da nova imagem,
   com as variáveis e a rede de produção disponíveis, antes de liberar a
   nova versão. Isso cria as tabelas do Telescope e corrige o campo `uuid`
   adicional da tabela `sessions`, permitindo persistir a autenticação.
5. Recrie os serviços da API, do worker e do scheduler com a nova imagem.
   O worker deve executar `php artisan queue:work redis`; o scheduler deve
   executar `schedule:run` a cada minuto, incluindo a limpeza diária do
   Telescope configurada em `routes/console.php`.

As migrations são uma etapa do deploy, não do build da imagem. O build não
precisa se conectar ao banco ou ao Redis.

### Roteamento e autenticação

- O frontend usa `/telescope` para solicitar acesso com o token Sanctum.
- O backend precisa receber `/api/telescope-access`, `/telescope-access/*`
  e `/telescope`, incluindo `/telescope/telescope-api/*`.
- Se frontend e backend compartilham o mesmo domínio, configure caminhos
  distintos para os dois painéis ou um domínio próprio para a API: ambos
  usam `/telescope` por padrão. Ajustar apenas o Dockerfile não resolve
  esse conflito no proxy.
- O usuário precisa ser autorizado pelo gate `viewTelescope`.
- A versão instalada do Telescope embute seus arquivos CSS e JavaScript na
  página; não é necessário publicar assets em `public/vendor/telescope`.

O Compose de produção deve apontar seus contextos de build e o volume
`public` do Nginx para os diretórios usados no servidor. No arquivo atual,
esses caminhos são `../api` e `../frontend`; confira-os ao montar o pacote.
