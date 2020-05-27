[README](../README.md) |
[Descrição funcional](descricao_funcional.md) | 
Descrição técnica

---

# Descrição técnica

O sistema é dividido em API, FRONT Web e Mobile (futuro)

A API é do tipo REST e comporta os endpoints responsáveis por toda a lógica e persistência. A aplicação FRONT é uma interface para realizar a votação e o gerenciamnto das votações. A aplicação mobile é utilizada somente no processo de votação.

[API](#api) |
[FRONT web](#front-web) |
[Mobile](#mobile)

---

## API

### Endpoints

```
GET /votacao/<hash de sessão de votação> (tam = 20)
    Retorna sessão

GET /votacao/<hash de ticket> (tam = 25)
    Retorna sessão 

POST /votacao/<hash de ticket> (tam = 25)
    acao=obterTokenFechado
    acao=obterPdf

GET /votacao/<hash de sessão de votação>/<token> 
    -> se for token de votação
        se estiver fechado, mostra a lista de votações da sessão
        se estiver aberto, mostra os dados de uma votação
    -> se for token de painel
        mostra os dados da tela
    -> se for token de gerente
        mostra a lista e os controles
    OBS.: Ao invés de expor o id, as sessões de votação serão acessadas por meio de hash gerada na criação da sessão.

POST /votacao/<hash de sessão de votação>/<token> 
    (votacao) acao=8
    (apoio) acao = 0,1,2,3,4,5,6,7,9,10

```

## FRONT web

A aplicação front cuida da autenticação e autorização. A API cuida dos dados e da lógica. 
A tela inicial deve ter o login único e um form para entrada de token. Se o usuário digitar o token irá para o site de votação. Se fizer login, irá para o site de gerenciamento.

## Mobile

Pode ser um app que carrega uma página web.
Deve ter um leitor de qrcode embutido.
A ser usado somente na votação como gerente ou eleitor. A parte de gerenciamento deverá ser no site FRONT.

## Principais Bibliotecas

* api: [uspdev/webservice](https://github.com/uspdev/webservice)
* rotas: [flight](http://flightphp.com)
* orm: [redbean](http://redbeanphp.com)
* template: [raelgc/template](https://github.com/raelgc/template)
* layout: [bootstrap4](https://getbootstrap.com), [bootstrap-table](https://bootstrap-table.com/), [jquery](https://jquery.com), [font awesome](https://fontawesome.com)
  
