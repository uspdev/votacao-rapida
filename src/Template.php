<?php

namespace Uspdev\Votacao;

class Template extends \raelgc\view\Template
{
    function __construct($addFile)
    {
        parent::__construct(TPL . '/main_template.html');
        $this->wwwroot = getenv('WWWROOT');

        $this->addFile('corpo', TPL . '/' . $addFile);
    }

    public function show($topbar='')
    {
        if ($topbar) {
            $this->topbar_class = $topbar->class;
            $this->block($topbar->block);
        } else {
            $this->topbar_class='top-bar-no-user';
            $this->block('block_no_user');

        }
        parent::show();

    }
}
