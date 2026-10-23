# Sistema de votação

## Motivação

No dia 11/3 na reunião do CO (COCEX ???) foi utilizado um sistema de votação eletrônica para eleição fechada (votos anônimos). Cada membro da reunião recebeu um papelete com um qrcode que direcionava para um formulário google que apresentava a cédula de votação. O diretor da EESC gostou do sistema e solicitou que fosse implantado um sistema similar em votação a ser realizada na Econ da unidade. A votação eletrônica foi realizada utilizando google forms com várias regras de geração de tokens, validação dos votos, totalização, etc, muitos desses passos sendo feito manualmente. Em reunião (google meet) realizada no dia 17/3/2020 com o Bruno, verificamos a viabilidade de se desenvolver um site em PHP a fim de atender a essa finalidade e permitir a automação de muitas das tarefas manuais empregadas até então.

## Objetivo

O objetivo desse sistema é fornecer uma plataforma de votação eletrônica a fim de atender reuniões de colegiados. A votação ocorre por meio de um token que identifica cada voto. O token pode ser ou não associado à uma pessoa. Em reuniões presenciais o token é distribuído em papel com QRCode, em reuniões por videoconferencia o token é distribuído por email.

## Dependências

* Servidor web (Apache ou Nginx)
* PHP >= 7.4 (compatível com PHP 8.x)
* Composer 2
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

### Observações sobre Rede e E-mails

* **Comunicação interna da API:**
  Caso o servidor esteja atrás de proxy/firewall que bloqueie loopback para o próprio IP público (sem Hairpin NAT), defina no `.env`:
  ```env
  API_URL='http://127.0.0.1/pasta_raiz/api'
  ```
  Isso permite que o PHP acesse a API localmente via HTTP na porta 80, enquanto os usuários acessam via HTTPS normalmente.

* **Servidor de E-mail (opcional em desenvolvimento):**
  Caso não configure servidor SMTP no `.env` (ou use `EMAIL_AUTH_TYPE='log'`), os e-mails com as credenciais e links de votação serão salvos em `local/log/emails_YYYY-MM.log`.

* **Servidor web de desenvolvimento:**
  Mesmo em desenvolvimento, recomenda-se utilizar Apache ou Nginx em vez do servidor embutido do PHP (`php -S`), pois o sistema realiza chamadas concorrentes entre front-end e API.

## Mais informações

[Changelog](doc/changelog.md)

[Descrição funcional](doc/descricao_funcional.md)

[Descrição técnica](doc/descricao_tecnica.md)

