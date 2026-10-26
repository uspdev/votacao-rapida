<?php

namespace Uspdev\Votacao\View;

use \RedBeanPHP\R as R;
use Uspdev\Votacao\Model\Log;
use Uspdev\Votacao\View\sessaoPhp as SS;

class Admin
{
    public static function home($aba)
    {
        // se nao for informado a aba, vamos usar log por padrão
        if ($aba == '') {
            header('Location: ' . $_SERVER['REDIRECT_URL'] . '/log');
            exit;
        }

        $tpl = new Template('admin/home.html');

        if ($aba == 'usuario') {
            $endpoint = '/admin/listarUsuario?codpes=' . SS::getUser()['codpes'];
            $usuarios = Api::send($endpoint);
            foreach ($usuarios as $u) {
                $tpl->U = $u;
                $tpl->block('block_usuario_list');
            }
        }

        if ($aba == 'log') {
            $logs = Log::listar();
            foreach ($logs as $log) {
                $item = json_decode($log);
                if (!$item || !is_object($item)) {
                    continue;
                }
                $item->context = !empty($item->context) ? json_encode($item->context, JSON_UNESCAPED_UNICODE) : '';
                $item->extra = !empty($item->extra) ? json_encode($item->extra, JSON_UNESCAPED_UNICODE) : '';
                $tpl->log = $item;
                $tpl->block('block_log_list');
            }
        }

        if ($aba == 'email') {
            R::selectDatabase('votacao');
            $emails = R::findAll('email', 'ORDER BY id DESC');
            foreach ($emails as $email) {
                $e = new \stdClass();
                $e->id = $email->id;
                $e->destinatario = htmlspecialchars(self::safeUnserialize($email->destinatario) ?: '-');
                $e->assunto = htmlspecialchars(self::safeUnserialize($email->assunto) ?: '-');
                $e->bcc = htmlspecialchars(self::safeUnserialize($email->bcc) ?: '');

                if (!empty($email->enviado)) {
                    $e->enviado = htmlspecialchars($email->enviado);
                    $e->status = 'Enviado';
                    $e->status_badge = '<span class="badge badge-success">Enviado</span>';
                } else {
                    $e->enviado = 'Pendente';
                    $e->status = 'Pendente';
                    $e->status_badge = '<span class="badge badge-warning">Pendente</span>';
                }

                if (!empty($email->sessao_id)) {
                    $sessao = R::load('sessao', $email->sessao_id);
                    if ($sessao && $sessao->id) {
                        $e->sessao_nome = htmlspecialchars($sessao->nome ?: ('#' . $sessao->id));
                        $e->sessao_link = '<a href="gerente/' . $sessao->id . '">' . $e->sessao_nome . '</a>';
                    } else {
                        $e->sessao_nome = '#' . $email->sessao_id;
                        $e->sessao_link = '<span class="text-muted">#' . $email->sessao_id . '</span>';
                    }
                } else {
                    $e->sessao_nome = 'Sem sessão';
                    $e->sessao_link = '<span class="text-muted">Sem sessão</span>';
                }

                $tpl->E = $e;
                $tpl->block('block_email_list');
            }
        }

        if ($aba == 'controle') {
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
        }

        $tpl->show('userbar');
    }

    /**
     * Exibe o corpo renderizado de um e-mail específico para visualização em iframe.
     *
     * @param int $id ID do e-mail
     * @return void
     */
    public static function verEmail($id)
    {
        if (!SS::isAdmin()) {
            \Flight::notFound();
            return;
        }

        R::selectDatabase('votacao');
        $email = R::load('email', (int)$id);
        if (!$email || !$email->id) {
            \Flight::notFound();
            return;
        }

        $corpo = self::safeUnserialize($email->corpo);
        $embedded = self::safeUnserialize($email->embedded);

        if (!empty($embedded) && is_array($embedded)) {
            foreach ($embedded as $emb) {
                if (!empty($emb['nome']) && !empty($emb['data'])) {
                    $mime = 'image/png';
                    if (preg_match('/\.jpe?g$/i', $emb['nome'])) {
                        $mime = 'image/jpeg';
                    } elseif (preg_match('/\.gif$/i', $emb['nome'])) {
                        $mime = 'image/gif';
                    }
                    $corpo = str_replace('cid:' . $emb['nome'], 'data:' . $mime . ';base64,' . $emb['data'], $corpo);
                }
            }
        }

        if (empty($corpo)) {
            $alt = self::safeUnserialize($email->alt);
            if (!empty($alt)) {
                $corpo = '<pre style="font-family: monospace; padding: 15px; white-space: pre-wrap;">' . htmlspecialchars($alt) . '</pre>';
            } else {
                $corpo = '<div style="padding: 20px; color: #666; font-style: italic;">E-mail sem conteúdo cadastrado.</div>';
            }
        }

        header('Content-Type: text/html; charset=utf-8');
        echo $corpo;
        exit;
    }

    /**
     * Desserializa de forma segura dados que possam ter sido serializados pelo RedBean/Model.
     *
     * @param mixed $val Valor a ser desserializado
     * @return mixed Valor original ou desserializado
     */
    protected static function safeUnserialize($val)
    {
        if (is_string($val)) {
            $un = @unserialize($val);
            if ($un !== false || $val === 'b:0;') {
                return $un;
            }
        }
        return $val;
    }
}
