<?php namespace Uspdev\Votacao\Model;

class Sessao
{
    public function __construct($hash)
    {
        // 'select * from sessao where hash=$hash'
        $this->id = 23;
        $this->unidade = 'EESC';
        $this->ano = 2020;
        $this->nome = 'Primeira sesão de votação eletrônica';
        $this->hash = $hash;
        $this->estado = 'fechado';
        $this->tipo_votacao = 'aberta';
        $this->link_qrcode = '';
        $this->link_manual = '';
        $this->lista = '';
        $this->votacoes = $this->listarVotacoes();

    }

    public function validaToken($token)
    {
        switch ($token) {
            case 'tokenv':
                return 'votacao';
                break;
            case 'tokena':
                return 'apoio';
                break;
            case 'tokent':
                return 'tela';
                break;
            case 'tokenr':
                return 'recepcao';
                break;
            default:
                return false;
        }
    }

    public function listarVotacoes()
    {
        $votacoes = [
            ['id' => 1, 'nome' => 'Votação 1', 'estado' => '0'],
            ['id' => 2, 'nome' => 'Votação 2', 'estado' => '0'],
        ];
        return $votacoes;
    }
}
