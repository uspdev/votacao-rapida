# AGENTS.md — Guia de Desenvolvimento para Agentes de IA

Este documento reúne o contexto técnico, decisões arquiteturais, convenções e pontos críticos do projeto **Votação Rápida** (`uspdev/votacao`), servindo como base de conhecimento para agentes autônomos e desenvolvedores.

---

## 1. Visão Geral do Projeto

O **Votação Rápida** é uma plataforma desenvolvida no âmbito da Universidade de São Paulo (USP) para realização e apuração de votações eletrônicas em reuniões e assembleias (ex: congregações, departamentos, colegiados).

* **Repositório:** `uspdev/votacao`
* **Licença:** GPL-3.0-or-later
* **Linguagem / Runtime:** PHP >= 8.2 (compatível e testado com PHP 8.2 e 8.3)
* **Gerenciador de Pacotes:** Composer 2 (com repositório `asset-packagist.org`)
* **Banco de Dados:** MySQL / MariaDB (e SQLite suportado para desenvolvimento)

---

## 2. Estrutura de Diretórios

```
├── app/                  # Bootstrap, inicialização de banco, constantes e funções
│   └── bootstrap.php     # Ponto central de inicialização (Flight, RedBean, error reporting)
├── cli/                  # Scripts utilitários de linha de comando
│   ├── processarEmailsFila.php  # Worker de envio da fila de e-mails
│   ├── atualizar_estrutura.php  # Atualizador de schema legado
│   └── salvarAdmin.php   # Cadastro de administrador inicial
├── config/               # Configuração centralizada da aplicação
│   └── app.php           # Leitura do .env com definição de valores padrão e helper config()
├── doc/                  # Documentação funcional, técnica e changelog
├── local/                # Diretório de escrita em runtime (logs, cache) - DEVE TER PERMISSÃO 775
│   └── log/              # Logs rotativos do Monolog (info-YYYY-MM-DD.log) e emails_YYYY-MM.log
├── public/               # Raiz pública servida pelo Apache/Nginx
│   ├── index.php         # Entrypoint das rotas Web (Flight)
│   ├── api/              # Entrypoint da API interna (Webservice Basic Auth)
│   │   ├── index.php
│   │   └── .htaccess     # Contém CGIPassAuth On
│   ├── assets/           # Dependências de frontend (Bootstrap, jQuery, Font Awesome, etc.)
│   └── media/            # Imagens estáticas (logos USP)
├── sql/                  # Migrations SQL e seeds estruturados
│   ├── provisionar.php   # Script unificado para inicialização do banco de dados
│   ├── sql1_migration_inicial.sql
│   └── ...
├── src/                  # Código-fonte da aplicação (PSR-4 namespace Uspdev\Votacao)
│   ├── Controller/       # Controladores da API e regras de negócio de apoio/gerente
│   ├── Model/            # Modelos de domínio (Email, Log, Sessao, Token, Usuario, etc.)
│   └── View/             # Controladores de tela/renderização web (Admin, Gerente, Run, etc.)
├── template/             # Templates HTML processados pelo raelgc/template
└── composer.json         # Dependências do backend e assets
```

---

## 3. Arquitetura e Comunicação Interna

A aplicação é dividida em duas camadas fundamentais que residem no mesmo repositório:

1. **API Interna (`/public/api` via `uspdev/webservice`):**
   * Controladores em `src/Controller/`.
   * Autenticação via **HTTP Basic Auth** (`API_USER` e `API_PWD` definidos no `.env`).
   * Responsável pelas regras de negócio da eleição, estados da sessão, tokens e persistência.

2. **Front-end Web (`/public/index.php` via `FlightPHP`):**
   * Controladores de visualização em `src/View/`.
   * Realiza a autenticação de usuários via OAuth Senha Única USP.
   * Comunica-se com a API interna por meio da classe `src/View/Api.php` (chamadas cURL com Basic Auth).

---

## 4. Pilha Tecnológica e Principais Dependências

* **Framework de Rotas:** `flightphp/core: ^3.0`
* **ORM / Persistência:** `gabordemooij/redbean: ^5.7`
* **Template Engine:** `raelgc/template: ^2.2`
* **Autenticação USP:** `uspdev/senhaunica: ^2.0` (chamadas estáticas `Senhaunica::login($credentials)`)
* **API Framework:** `uspdev/webservice: 1.*`
* **Envio de E-mail:** `phpmailer/phpmailer: ^6.1`
* **Logging:** `monolog/monolog: ^2.0` (RotatingFileHandler com JsonFormatter)
* **Frontend:**
  * Bootstrap 4.6 (`bower-asset/bootstrap4`)
  * Bootstrap Table 1.16 (`npm-asset/bootstrap-table`) com extensão `filter-control`
  * Font Awesome 5 (`npm-asset/fortawesome--fontawesome-free`)
  * jQuery 3.5 (`npm-asset/jquery`)

---

## 5. Particularidades Críticas e "Gotchas"

Ao modificar este projeto, atenção especial deve ser dada aos seguintes itens:

### 5.1. Comunicação cURL e Redirecionamento Local (`CURLOPT_RESOLVE` / `API_URL`)
* Em servidores atrás de firewall/proxy reverso sem Hairpin NAT (loopback para o IP público bloqueado), o cURL do PHP falha ao tentar acessar a URL pública de si mesmo.
* Para resolver isso automaticamente **sem necessidade de alterar o `/etc/hosts`**, a classe `src/View/Api.php` utiliza `CURLOPT_RESOLVE` para mapear o hostname do `API` / `WWWROOT` diretamente para `127.0.0.1` em memória.
* Isso garante compatibilidade imediata com **VirtualHosts** baseados em nome (`ServerName`), pois o cURL conecta no loopback local mas preserva o cabeçalho `Host: seu-dominio`.
* Caso necessário, o arquivo `.env` ainda suporta a diretiva `API_URL` para sobrescrever a URL completa ou `API_RESOLVE_IP` (para mudar o IP de resolução ou desativar com `none`).

### 5.2. Apache + PHP-FPM (FastCGI) e Basic Auth
* Servidores Apache que usam PHP-FPM descartam os cabeçalhos `Authorization: Basic ...` por padrão.
* O arquivo `public/api/.htaccess` deve conter `CGIPassAuth On`.
* O arquivo `public/api/index.php` contém um polyfill em PHP para extrair credenciais via `REDIRECT_HTTP_AUTHORIZATION` quando necessário.

### 5.3. Flight v3 Buffer de Saída
* No Flight v3, a chamada `Flight::json(...)` apenas prepara o buffer; ela não o descarrega de imediato se o código cliente executar um `exit;` prematuro (como ocorre no `uspdev/webservice`).
* Para garantir a entrega correta das respostas da API, o arquivo `app/bootstrap.php` mantém configurado:
  ```php
  \Flight::set('flight.v2.output_buffering', true);
  \Flight::response()->v2_output_buffering = true;
  ```

### 5.4. Template Engine (`raelgc/template`) no PHP 8.x
* O `raelgc/template` executa internamente:
  ```php
  $s = str_replace("{".$var.$properties."}", $pointer, $s);
  ```
* No PHP 7, passar um `stdClass` ou `array` em `$pointer` não gerava erro fatal quando a variável não existia no template. No **PHP 8.0+**, o `str_replace()` lança um `TypeError` imediato.
* **Regra de ouro:** **NUNCA** atribua objetos complexos ou arrays diretamente a propriedades de template que possam ser avaliadas em `subst()`. Pré-formate os valores como strings escalares (ex: `json_encode($data, JSON_UNESCAPED_UNICODE)`) antes de passar para o template.

### 5.5. Volume de E-mails e Paginação Server-Side
* A tabela `email` armazena mensagens com campos serializados via `serialize(...)` e pode ultrapassar dezenas de milhares de registros (55.000+).
* **NUNCA** faça `R::findAll('email')` sem cláusula `LIMIT`.
* A visualização no painel administrativo (`/admin/email`) utiliza paginação server-side (`data-side-pagination="server"`) com requisições AJAX para `admin/email/listar`, carregando apenas a página solicitada (ex: 25 registros) com contagem rápida via `R::count()`.

### 5.6. Envio de E-mails vs. Modo Log
* Se `EMAIL_AUTH_TYPE='log'` ou se `EMAIL` estiver vazio, o sistema opera em modo de simulação.
* O método `Email::dispararProcessamentoFila()` detecta o modo log e processa imediatamente no processo atual (levando `< 1ms`), evitando dependência do `exec()` em background.
* Em modo SMTP, `Email::dispararProcessamentoFila()` utiliza `Email::getPhpCli()` para resolver o binário CLI do PHP (`/usr/bin/php`), evitando o erro de execução de `/usr/sbin/php-fpm*` (que ocorre ao utilizar `PHP_BINARY` no PHP-FPM). A saída é direcionada para `local/log/email_worker.log`.
* O status é registrado no banco como `YYYY-MM-DD HH:MM:SS (log)` ou `YYYY-MM-DD HH:MM:SS` e no painel admin com seus respectivos badges.
* Os corpos dos e-mails são gravados em `local/log/emails_YYYY-MM.log`.
* O script `cli/processarEmailsFila.php` utiliza trava exclusiva não-bloqueante (`flock` em `local/log/email_queue.lock`) para evitar concorrência e duplicidade de disparos entre cliques manuais e o cron.
* **Recomendação para Produção:** Agendar cron job a cada minuto para garantir o esvaziamento contínuo da fila:
  ```cron
  * * * * * cd /caminho/do/projeto && php cli/processarEmailsFila.php >> local/log/email_worker.log 2>&1
  ```

### 5.7. PHP 8.2+ Depreciações e Tratamento de Erro na API
* No PHP 8.2+, a criação de propriedades dinâmicas emite avisos `E_DEPRECATED`. O Flight PHP converte qualquer aviso de erro coberto pelo `error_reporting()` em `ErrorException`, gerando HTTP 500 caso `E_DEPRECATED` esteja ativo (padrão em produção).
* O `app/bootstrap.php` define globalmente `error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);` tanto para `dev` quanto para `prod`.
* Os controladores da API (`src/Controller/`) utilizam o atributo `#[\AllowDynamicProperties]` e declaram propriedades públicas (`$query`, `$data`, `$method`, `$files`) para recepção de requisições pelo `uspdev/webservice`.
* O `public/api/index.php` implementa manipulador customizado `Flight::map('error', ...)` retornando JSON padronizado com código 500 e logando a exceção no PHP error_log. Em ambiente `dev`, o JSON inclui também a classe da exceção, arquivo, linha e stack trace.
* O consumidor `src/View/Api.php` exibe automaticamente na tela, quando em ambiente `dev`, mensagens detalhadas e formatadas (código HTTP, endpoint, postdata, resposta com trace e log verbose do cURL) caso ocorra qualquer erro HTTP (>= 400).

### 5.8. Segurança e Restrição de Acesso à API (`uspdev/ip-control`)
* A API interna (`/public/api`) destina-se exclusivamente a chamadas do front-end web em loopback local (`127.0.0.1`).
* O arquivo `public/api/index.php` utiliza `\Uspdev\Ipcontrol\Ipcontrol::proteger(false)` para restringir o acesso por IP.
* Por padrão, caso `USPDEV_IP_CONTROL` não seja definido no `.env`, o sistema assume `localhost`, rejeitando automaticamente requisições externas com HTTP 403.
* Pode ser configurado como `USPDEV_IP_CONTROL='localhost'` ou `USPDEV_IP_CONTROL='whitelist'`.

---

## 6. Comandos e Procedimentos Frequentes

### 6.1. Provisionamento do Banco de Dados (Instalação Inicial)
Para criar tabelas, executar migrações e aplicar seeds iniciais em banco vazio:
```bash
php sql/provisionar.php
```

### 6.2. Atualização do Banco de Dados (Migrations em Produção / Atualizações)
Para verificar e aplicar automaticamente apenas as migrations incrementais pendentes:
```bash
php sql/atualizar.php
```

### 6.3. Processamento Manual da Fila de E-mails
```bash
php cli/processarEmailsFila.php
```

### 6.4. Validação do Composer
```bash
composer validate
```

### 6.5. Atualização de Lock sem alterar dependências
```bash
composer update --lock
```

### 6.6. Permissões de Pastas em Desenvolvimento
```bash
chmod -R 775 local/
```

---

## 7. Padrões de Código e Convenções

* **Estilo de Código:** PSR-12 para formatação de PHP.
* **Documentação:** Toda classe e método público ou protegido em `src/Model/`, `src/View/` e `src/Controller/` deve possuir blocos **DocBlock (PHPDoc)** completos (`@param`, `@return`, descrições em português).
* **Mensagens de Commit:** Commits atômicos no padrão Conventional Commits (ex: `feat:`, `fix:`, `docs:`, `chore:`).

