<?php

namespace Uspdev\Votacao;

class Form
{
    protected $out = '';
    
    function __construct($data)
    {
        $this->data = $data;
        $this->titulo();
        $this->checkbox();
        $this->acao();
        $this->wrap();
    }

    public function render()
    {
        return $this->out;
    }

    function titulo()
    {
        $out = '<h3>' . $this->data->nome . ' (' . $this->data->tipo . ')</h3>' . PHP_EOL;
        $this->out .= $out;
    }

    function wrap()
    {
        $out = '<div class="form">' . PHP_EOL;
        $out .= '<form method="POST">' . PHP_EOL;
        $out .= $this->out;
        $out .= '</form>' . PHP_EOL;
        $out .= '</div>' . PHP_EOL;
        $this->out = $out;
    }

    function checkbox()
    {
        $alts = $this->data->alternativas;
        $out = '<div>' . PHP_EOL;
        foreach ($alts as $a) {
            $out .= '<input type="checkbox" name="alternativa_id" value="' . $a->id . '"> ' . $a->texto . '<br>' . PHP_EOL;
        }
        $out .= '</div>' . PHP_EOL;
        $this->out .= $out;
    }

    function acao()
    {
        $acao = $this->data->acao;
        $out = '<input type="hidden" name="acao" value="' . $acao->cod . '">' . PHP_EOL;
        $out .= '<input type="hidden" name="votacao_id" value="' . $this->data->id . '">' . PHP_EOL;
        $out .= '<br>';
        $out .= '<input type="submit" name="Enviar">' . PHP_EOL;
        $this->out .= $out;
    }
}
