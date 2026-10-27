# Sistema de votação

## Motivação

No dia 11/03/2020 na reunião do CO foi utilizado um sistema de votação eletrônica para eleição fechada (votos anônimos). Cada membro da reunião recebeu um papelete com um qrcode que direcionava para um formulário google que apresentava a cédula de votação. A votação eletrônica foi realizada utilizando google forms com várias regras de geração de tokens, validação dos votos, totalização, etc, muitos desses passos sendo feito manualmente. 

O diretor da EESC gostou do sistema e solicitou que fosse implantado um sistema similar em votação a ser realizada na Econ da unidade. 

Em reunião (google meet) realizada no dia 17/3/2020 com o Bruno, verificamos a viabilidade de se desenvolver um site em PHP a fim de atender a essa finalidade e permitir a automação de muitas das tarefas manuais empregadas até então.

## Objetivo

O objetivo desse sistema é fornecer uma plataforma de votação eletrônica a fim de atender reuniões de colegiados. 

A votação ocorre por meio de um token que identifica cada voto. O token pode ser ou não associado à uma pessoa. O token pode ser distribuído em papel com QRCode ou distribuído por email, atendendo encontros presenciais ou online.

## Dependências

* Servidor web (Apache ou Nginx)
* PHP >= 8.2
* ext-curl
* ext-sqlite3
* ext-pdo_mysql (caso utilize MySQL/MariaDB)

## Instalação e configuração

* `git clone <url_repositorio>`
* `composer install`
* `cp .env.example .env`
* Ajuste o `.env` conforme necessário (URLs, banco de dados, credenciais da Senha Única)
* Provisione o banco de dados (migrations e seeds iniciais):
  ```bash
  php sql/provisionar.php
  ```
* Ajuste as permissões da pasta `local/` e subpastas para que o servidor web possa gravar:
  ```bash
  chmod -R 775 local/
  ```
* Cadastre na Senha Única USP a URL de retorno (callback): `<WWWROOT>/login`

## Atualização da aplicação

Para atualizar uma instalação existente (em desenvolvimento ou produção):

1. Obtenha a versão mais recente do código:
   ```bash
   git pull
   ```
2. Atualize as dependências:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
   *(ou apenas `composer install` em desenvolvimento)*
3. Aplique as migrações de banco de dados pendentes:
   ```bash
   php sql/atualizar.php
   ```
   *O script verifica quais migrações já foram executadas e aplica automaticamente apenas as pendentes, registrando o histórico na tabela `migration`.*
4. Verifique as permissões de escrita:
   ```bash
   chmod -R 775 local/
   ```

### Observações sobre Rede e E-mails

* **Comunicação interna da API e VirtualHosts:**
  O sistema resolve o host do `WWWROOT` automaticamente para `127.0.0.1` em memória (`CURLOPT_RESOLVE`), permitindo funcionamento em VirtualHosts e servidores sem Hairpin NAT sem necessidade de alterar o `/etc/hosts`. Caso precise sobrescrever manualmente, defina `API_URL='http://...'` no `.env`.

* **Servidor de E-mail (opcional em desenvolvimento):**
  Caso não configure servidor SMTP no `.env` (ou use `EMAIL_AUTH_TYPE='log'`), os e-mails com as credenciais e links de votação serão salvos em `local/log/emails_YYYY-MM.log`.

* **Fila de E-mails e Cron Job (Produção):**
  Os e-mails gerados pelo sistema são registrados em fila na tabela `email`. A aplicação tenta disparar o processamento em background automaticamente via PHP CLI (`cli/processarEmailsFila.php`).
  Para garantir o envio ininterrupto e confiável em ambiente de produção, configure a execução a cada minuto na **crontab** do servidor (`crontab -e` do usuário que executa a aplicação):
  ```cron
  * * * * * cd /caminho/votacao-rapida && php cli/processarEmailsFila.php >> local/log/email_worker.log 2>&1
  ```
  *(Substitua `/caminho` pelo caminho absoluto da aplicação no servidor).*

* **Servidor web de desenvolvimento:**
  Mesmo em desenvolvimento, recomenda-se utilizar Apache ou Nginx em vez do servidor embutido do PHP (`php -S`), pois o sistema realiza chamadas concorrentes entre front-end e API.

## Mais informações

[Changelog](doc/changelog.md)

[Descrição funcional](doc/descricao_funcional.md)

[Descrição técnica](doc/descricao_tecnica.md)

