<?php

namespace Uspdev\Votacao\View {

    use Uspdev\Votacao\View\SessaoPhp as SS;

    class Template extends \raelgc\view\Template
    {
        function __construct($addFile)
        {
            parent::__construct(TPL . '/main_template.html');

            // vamos carregar algumas variáveis de uso geral
            $main = new \Stdclass;
            $main->wwwroot = getenv('WWWROOT');
            $main->self = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

            // Vamos pegar o papel e colocar no titulo. (gerente, painel, apoio, etc)
            $haystack = explode('/', $main->self);
            $subtitulo = $haystack[array_search(basename($main->wwwroot), $haystack) + 1];
            if (in_array($subtitulo, ['gerente', 'apoio', 'painel', 'ajuda', 'votacao'])) {
                $main->titulo = ucfirst($subtitulo) . ' | Votação Rápida';
            } else {
                $main->titulo = 'Votação Rápida';
            }

            // vamos mostrar mensagem se necessário
            if ($msg = SS::getMsg()) {
                $main->msg = json_decode(json_encode($msg));
                $this->block('block_principal_msg');
            }

            //vamos obter avisos
            $main->avisos = SS::get('avisos');
            if ($main->avisos > 0) {
                $this->block('block_main_aviso_sim');
            } else  {
                $this->block('block_main_aviso_nao');
            }
            $this->main = $main;
            $this->addFile('corpo', TPL . '/' . $addFile);
        }

        public function show($bloco = '')
        {
            // vamos renderizar o userbar: logado, deslogado e barra fina
            if ($bloco == 'userbar') {
                $this->block('block_topo_img');
                $this->topbar_class = 'top-bar-user';
                if ($user = json_decode(json_encode(SS::get('user')))) {
                    $this->user = $user; // transformando array em obj
                    $this->block('block_user_in');
                    if (SS::isAdmin()) {
                        $this->block('blockmain_admin');
                    }
                } else {
                    $this->block('block_user_out');
                }
                $this->block('block_user_principal');
            } else {
                $this->topbar_class = 'top-bar-no-user';
                $this->block('block_no_user');
            }
            // vamos colocar uma tarja se estiver em dev ou teste
            if (getenv('AMBIENTE') == 'dev') {
                $this->block('block_dev');
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

    function tpl_showifnot($str, $cond)
    {
        return !$str ? $cond : $str;
    }

    function tpl_append($str, $pre, $pos = '')
    {
        return $pre . ' ' . $str . ' ' . $pos;
    }

    function nl2pipe($str)
    {
        return str_replace("\n", ' | ', $str);
    }
}
