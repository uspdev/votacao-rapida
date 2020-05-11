<?php

namespace Uspdev\Votacao\View;

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
        $out = '<h3>' . $this->data->nome . '</h3>' . PHP_EOL;
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
        //print_r($this->data);exit;
        if ($this->data->input_count == 1) {
            $type="radio";
        } else {
            $type="checkbox";
        }
        $out = '<div class="alternativas">' . PHP_EOL;
        foreach ($alts as $a) {
            $out .= '<div class="form-check space">'.PHP_EOL;
            $out .= '<input type="'.$type.'" class="form-check-input" name="alternativa_id" value="' . $a->id . '" id="check_' . $a->id . '"> '  . PHP_EOL;
            $out .= '<label class="form-check-label" for="check_' . $a->id . '">' . $a->texto . '</label>' . PHP_EOL;
            $out .= '</div>' . PHP_EOL;
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
        $out .= '<input type="submit" class="btn btn-primary" name="Enviar" value="Enviar">' . PHP_EOL;
        $this->out .= $out;
    }
}
