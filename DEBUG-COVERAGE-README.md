# Configuração de Debug e Coverage

Este projeto está configurado com **Xdebug** para debugging e **PCOV** para geração de coverage de testes.

## Xdebug

### Configuração
O Xdebug está configurado com:
- Porta: 9003
- IDE Key: PHPSTORM
- Host: host.docker.internal (para Docker)

### Como usar
1. Configure seu IDE para escutar na porta 9003
2. Defina breakpoints no código
3. Execute seus testes ou aplicação em modo debug

### Para PHPStorm:
1. Vá em File → Settings → PHP → Debug
2. Configure Xdebug port: 9003
3. Marque "Can accept external connections"

## PCOV (Code Coverage)

### Vantagens sobre Xdebug
- **Performance**: PCOV é significativamente mais rápido que Xdebug para coverage
- **Focado**: Desenvolvido especificamente para coverage, não debugging

### Como usar

#### Opção 1: Script automatizado (Recomendado)
```bash
./test-coverage.sh
```

#### Opção 2: Via Laravel Artisan
```bash
php artisan test --coverage --coverage-html=coverage-report
```

#### Opção 3: Via PHPUnit
```bash
php -d pcov.enabled=1 vendor/bin/phpunit --coverage-html=coverage-report --coverage-text
```

### Relatórios
- **HTML**: `coverage-report/index.html` - Relatório visual completo
- **Terminal**: Coverage summary no terminal

## Docker

### Build da imagem
```bash
docker build -f Dockerfile.local -t sessly-api .
```

### Executar container
```bash
docker run -p 8882:8882 -p 9003:9003 sessly-api
```

## Troubleshooting

### Xdebug não conecta
1. Verifique se o IDE está escutando na porta 9003
2. Confirme que o firewall não está bloqueando a porta
3. Para Docker, verifique se `host.docker.internal` resolve corretamente

### PCOV não gera coverage
1. Certifique-se que PCOV está habilitado: `php -m | grep pcov`
2. Verifique se há testes executando: `php artisan test`
3. Confirme que os arquivos estão no diretório configurado

### Performance
- Para desenvolvimento diário: Use apenas Xdebug
- Para coverage: Use PCOV (muito mais rápido)
- Evite rodar ambos simultaneamente

## Comandos úteis

```bash
# Verificar extensões instaladas
php -m

# Verificar configuração do Xdebug
php --ini | grep xdebug

# Verificar configuração do PCOV
php --ini | grep pcov

# Executar testes específicos com coverage
php artisan test --filter=PatientTest --coverage

# Executar apenas um teste específico
php artisan test --filter=parametrosInvalidos
```
