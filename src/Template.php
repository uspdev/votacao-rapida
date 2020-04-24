<?php

namespace Uspdev\Votacao {

    use Uspdev\Votacao\SessaoPhp as SS;

    class Template extends \raelgc\view\Template
    {
        function __construct($addFile)
        {
            parent::__construct(TPL . '/main_template.html');
            $this->wwwroot = getenv('WWWROOT');

            $this->addFile('corpo', TPL . '/' . $addFile);
        }

        public function show($bloco = '')
        {
            // vamos renderizar o userbar: logado, deslogado e barra fina
            if ($bloco == 'userbar') {
                $this->topbar_class = 'top-bar-user';
                if ($user = SS::getUser()) {
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
}
