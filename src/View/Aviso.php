<?php

namespace Uspdev\Votacao\View;

use Uspdev\Votacao\View\sessaoPhp as SS;
use League\CommonMark\CommonMarkConverter;

class Aviso
{
    const arq = ROOTDIR . '/doc/aviso/index.csv';

    public static function mostrar($id)
    {
        SS::getUser();
        $msgs = SELF::listar();
        $last_id = $msgs[0]->id;

        // vamos sanitizar o id
        $id = empty((int) $id) ? $last_id : $id;
        $id = ($id > $last_id) ? $last_id : $id;

        $tpl = new Template('aviso.html');

        $currmsg = SELF::obter($id, $msgs);
        $currmsg->prev_id && $tpl->block('block_msg_anterior');
        $currmsg->next_id && $tpl->block('block_msg_proximo');
        $tpl->M = $currmsg;
        $tpl->block('block_msg');

        foreach ($msgs as $msg) {
            $msg->class = ($msg->id == $currmsg->id) ? 'font-weight-bold' : '';
            $tpl->M = $msg;
            $tpl->block('block_msgs');
        }
        $tpl->show('userbar');
    }

    public static function obterUltimoId() {
        $msgs = SELF::listar();
        return $msgs[0]->id;
    }

    protected static function listar()
    {
        $handle = fopen(SELF::arq, 'r');
        // a primeira linha do arquivo é de cabeçalho
        $header = str_getcsv(fgets($handle), ';');

        $msgs = [];
        while (($line = fgets($handle)) !== false) {
            if (!empty(trim($line))) {
                $arr = str_getcsv($line, ';');
                $msg = new \stdClass;
                for ($i = 0; $i < count($header); $i++) {
                    $msg->{$header[$i]} = $arr[$i];
                }
                $last_id = $msg->id;
                $msgs[] = $msg;
            }
        }
        fclose($handle);
        rsort($msgs);
        return $msgs;
    }

    protected static function obter($id, $msgs)
    {
        $last_id = $msgs[0]->id;
        foreach ($msgs as $msg) {
            if ($msg->id == $id) {
                // convertendo de MD para HTML
                $conv = new CommonMarkConverter();
                $msg->corpo = $conv->convertToHtml(file_get_contents(ROOTDIR . '/doc/aviso/' . $msg->arq));

                // não é a mais antiga, vamos popular os dados da msg anterior
                $msg->prev_id = ($id == 1) ?  0 : $id - 1;

                // não é a mais recente
                $msg->next_id = ($id != $last_id) ? $id + 1 : 0;

                return $msg;
            }
        }
    }
}
