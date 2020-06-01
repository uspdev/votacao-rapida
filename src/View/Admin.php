<?php

namespace Uspdev\Votacao\View;

use \RedBeanPHP\R as R;
use Uspdev\Votacao\Model\Log;
use Uspdev\Votacao\View\sessaoPhp as SS;

class Admin
{
    public static function home()
    {
        $endpoint = '/admin/listarUsuario?codpes='.SS::getUser()['codpes'];
        $usuarios = Api::send($endpoint);
        //echo '<pre>';print_r($usuarios);exit;
        $tpl = new Template('admin/home.html');
        foreach ($usuarios as $u) {
            $tpl->U = $u;
            $tpl->block('block_usuarios');
        }

        // logs
        $logs = Log::listar();

        foreach ($logs as $log) {
            $tpl->log = json_decode($log);
            $tpl->block('block_log');
        }

        // vamos mostrar as relações entre estados e ações
        // Estado => Ação
        R::selectDatabase('votacao');
        $estados = R::findAll('estado');
        foreach ($estados as $e) {
            $acao_nome = '';
            // vamos expandir as acoes de cada estado
            foreach (explode(',', $e->acoes) as $acao_cod) {
                $acao = R::findOne('acao', 'cod = ?', [$acao_cod]);
                $acao_nome .= $acao->nome . ' | ';
            }
            $e->acoes = substr($acao_nome, 0, -2);
            $tpl->E = $e;
            $tpl->block('block_estado');
        }

        //Ação: Estado inicial -> estado final
        //$acoes = R::find('acao', "escopo = 'apoio'");
        $acoes = R::findAll('acao');
        foreach ($acoes as $a) {
            $a->estado = R::getCell('SELECT nome FROM estado WHERE cod = ?', [$a->estado]);
            $ini = R::getAll('SELECT nome FROM estado WHERE acoes LIKE ?', ["%$a->cod%"]);
            if (count($ini) == 1) {
                $a->estado_ini = $ini[0]['nome'];
            } else {
                $a->estado_ini = '';
                foreach ($ini as $i) {
                    $a->estado_ini .= $i['nome'] . ' | ';
                }
                $a->estado_ini = substr($a->estado_ini, 0, -2);
            }
            $tpl->A = $a;
            $tpl->block('block_acao');
        }

        $tpl->show('userbar');
    }
}
