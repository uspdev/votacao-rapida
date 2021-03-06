# Sistema de votação

## Motivação

No dia 11/3 na reunião do CO (COCEX ???) foi utilizado um sistema de votação eletrônica para eleição fechada (votos anônimos). Cada membro da reunião recebeu um papelete com um qrcode que direcionava para um formulário google que apresentava a cédula de votação. O diretor da EESC gostou do sistema e solicitou que fosse implantado um sistema similar em votação a ser realizada na Econ da unidade. A votação eletrônica foi realizada utilizando google forms com várias regras de geração de tokens, validação dos votos, totalização, etc, muitos desses passos sendo feito manualmente. Em reunião (google meet) realizada no dia 17/3/2020 com o Bruno, verificamos a viabilidade de se desenvolver um site em PHP a fim de atender a essa finalidade e permitir a automação de muitas das tarefas manuais empregadas até então.

## Objetivo

O objetivo desse sistema é fornecer uma plataforma de votação eletrônica a fim de atender reuniões de colegiados. A votação ocorre por meio de um token que identifica cada voto. O token pode ser ou não associado à uma pessoa. Em reuniões presenciais o token é distribuído em papel com QRCode, em reuniões por videoconferencia o token é distribuído por email.

## Dependências

* Servidor web (testado no apache mas deve funcionar em nginx)
* PHP 7.2
* ext-curl

## Instalação e configuração

* git clone
* composer install
* cp env .env
* Ajuste o .env conforme necessário
* Utilize AMBIENTE='dev' para criar as tabelas on the fly
* Rode `php cli\atualizar_estrutura.php`
* Rode `php cli\salvarAdmin.php` para cadastrar-se como admin
* Ajuste a permissão da pasta local e todo o conteúdo para que o apache possa escrever nele. Outra opção é usar o módulo mpm-itk do apache para que ele rode no mesmo usuário da aplicação (http://mpm-itk.sesse.net/)

É esperado que seja cadastrada a seguinte url de rertono (callback): /login

Caso o servidor esteja atrás de um proxy ou firewall é necessário cadastrar no `/etc/hosts` o domínio utilizado no sistema.
Se for o ambiente de desenvolvimento geralmente não é necessário.\
Ex.: 127.0.0.1 votacaorapida.dominio.usp.br

Mesmo em dev, não utilize o servidor interno do PHP pois ele é monotarefa e o sistema precisa de pelo menos dois processos ativos para funcionar.

## Mais informações

[Changelog](doc/changelog.md)

[Descrição funcional](doc/descricao_funcional.md)

[Descrição técnica](doc/descricao_tecnica.md)

