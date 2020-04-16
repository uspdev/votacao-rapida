<?php

namespace Uspdev\Votacao {

    class Template extends \raelgc\view\Template
    {
        function __construct($addFile)
        {
            parent::__construct(TPL . '/main_template.html');
            $this->wwwroot = getenv('WWWROOT');

            $this->addFile('corpo', TPL . '/' . $addFile);
        }

        public function show($topbar = '')
        {
            if ($topbar) {
                $this->topbar_class = $topbar->class;
                $this->block($topbar->block);
            } else {
                $this->topbar_class = 'top-bar-no-user';
                $this->block('block_no_user');
            }
            parent::show();
        }

        public static function setTopBar($tpl)
        {
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
