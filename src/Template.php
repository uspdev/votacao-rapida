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

    public function show($block = '')
    {
        if ($block) {
            $this->block($block);
        } else {
            parent::show();
        }
    }
}
