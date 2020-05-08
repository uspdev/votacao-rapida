<?php

namespace Uspdev\Votacao {

    use Uspdev\Votacao\SessaoPhp as SS;

    class Template extends \raelgc\view\Template
    {
        function __construct($addFile)
        {
            parent::__construct(TPL . '/main_template.html');

            $main = new \Stdclass;
            $main->wwwroot = getenv('WWWROOT');
            $main->self = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            
            // Vamos pegar o papel e colocar no titulo. (gerente, painel, apoio, etc)
            $haystack = explode('/', $main->self);
            $subtitulo = $haystack[array_search(basename($main->wwwroot), $haystack) + 1];
            $main->titulo = ucfirst($subtitulo) . ' | Votação Rápida';

            $this->main = $main;

            // vamos mostrar mensagem se necessário
            if ($msg = SS::getMsg()) {
                $this->PM = json_decode(json_encode($msg));
                $this->block('block_principal_msg');
            }

            $this->addFile('corpo', TPL . '/' . $addFile);
        }

        public function show($bloco = '')
        {
            // vamos renderizar o userbar: logado, deslogado e barra fina
            if ($bloco == 'userbar') {
                $this->topbar_class = 'top-bar-user';
                if ($user = SS::get('user')) {
                    $this->user = json_decode(json_encode($user)); // transformando array em obj
                    $this->block('block_user_in');
                } else {
                    $this->block('block_user_out');
                }
                $this->block('block_topo_img');
            } else {
                $this->topbar_class = 'top-bar-no-user';
                $this->block('block_no_user');
            }
            parent::show();
        }

        public static function erro($msg)
        {
            $tpl = new Template('erro.html');
            $tpl->msg = $msg;
            $tpl->show('userbar');
            exit;
        }
    }
}

namespace {
    function show_if($str, $cond)
    {
        return $str ? $cond : '';
    }

    function tpl_append($str, $pre, $pos = '')
    {
        return $pre . ' ' . $str . ' ' . $pos;
    }

    function nl2pipe($str) {
        return str_replace("\n", ' | ', $str);
    }
}
