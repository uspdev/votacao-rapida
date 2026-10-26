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
            $tpl->block('block_email');
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
     * Retorna a listagem paginada e filtrada de e-mails em formato JSON para o Bootstrap Table.
     *
     * @return void
     */
    public static function listarEmails()
    {
        if (!SS::isAdmin()) {
            \Flight::notFound();
            return;
        }

        R::selectDatabase('votacao');

        $whereClauses = ['1=1'];
        $params = [];

        // Busca global
        $search = trim($_GET['search'] ?? '');
        if (!empty($search)) {
            $whereClauses[] = '(destinatario LIKE ? OR assunto LIKE ? OR enviado LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        // Filtros específicos por coluna
        $filterStr = $_GET['filter'] ?? '';
        $filter = is_string($filterStr) ? json_decode($filterStr, true) : [];
        if (is_array($filter)) {
            if (!empty($filter['status'])) {
                if ($filter['status'] === 'Enviado') {
                    $whereClauses[] = 'enviado IS NOT NULL AND enviado NOT LIKE ?';
                    $params[] = '%(log)%';
                } elseif ($filter['status'] === 'Log') {
                    $whereClauses[] = 'enviado LIKE ?';
                    $params[] = '%(log)%';
                } elseif ($filter['status'] === 'Pendente') {
                    $whereClauses[] = 'enviado IS NULL';
                }
            }
            if (!empty($filter['destinatario'])) {
                $whereClauses[] = 'destinatario LIKE ?';
                $params[] = '%' . trim($filter['destinatario']) . '%';
            }
            if (!empty($filter['assunto'])) {
                $whereClauses[] = 'assunto LIKE ?';
                $params[] = '%' . trim($filter['assunto']) . '%';
            }
            if (!empty($filter['enviado'])) {
                $whereClauses[] = 'enviado LIKE ?';
                $params[] = '%' . trim($filter['enviado']) . '%';
            }
            if (!empty($filter['sessao'])) {
                $sTerm = trim($filter['sessao']);
                if (is_numeric($sTerm)) {
                    $whereClauses[] = 'sessao_id = ?';
                    $params[] = (int)$sTerm;
                } else {
                    $sessIds = R::getCol('SELECT id FROM sessao WHERE nome LIKE ?', ['%' . $sTerm . '%']);
                    if (!empty($sessIds)) {
                        $whereClauses[] = 'sessao_id IN (' . R::genSlots($sessIds) . ')';
                        $params = array_merge($params, array_values($sessIds));
                    } else {
                        $whereClauses[] = '1=0';
                    }
                }
            }
        }

        $whereSql = implode(' AND ', $whereClauses);
        $total = R::count('email', $whereSql, $params);

        $allowedSorts = [
            'id' => 'id',
            'enviado' => 'enviado',
            'destinatario' => 'destinatario',
            'assunto' => 'assunto'
        ];
        $sortKey = $_GET['sort'] ?? 'id';
        $sortField = $allowedSorts[$sortKey] ?? 'id';
        $sortOrder = strtoupper($_GET['order'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

        $limit = max(1, min(100, (int)($_GET['limit'] ?? 25)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $sql = "$whereSql ORDER BY $sortField $sortOrder LIMIT $limit OFFSET $offset";
        $emails = R::findAll('email', $sql, $params);

        $sessaoIds = array_values(array_filter(array_unique(array_map(function ($e) {
            return $e->sessao_id;
        }, $emails))));
        $sessoes = [];
        if (!empty($sessaoIds)) {
            $sRows = R::find('sessao', 'id IN (' . R::genSlots($sessaoIds) . ')', $sessaoIds);
            foreach ($sRows as $s) {
                $sessoes[$s->id] = $s->nome;
            }
        }

        $rows = [];
        foreach ($emails as $email) {
            $dest = self::safeUnserialize($email->destinatario) ?: '-';
            $assunto = self::safeUnserialize($email->assunto) ?: '-';

            if (empty($email->enviado)) {
                $enviado = 'Pendente';
                $statusBadge = '<span class="badge badge-warning">Pendente</span>';
            } elseif (stripos($email->enviado, '(log)') !== false) {
                $enviado = htmlspecialchars($email->enviado);
                $statusBadge = '<span class="badge badge-info">Salvo em Log</span>';
            } else {
                $enviado = htmlspecialchars($email->enviado);
                $statusBadge = '<span class="badge badge-success">Enviado</span>';
            }

            if (!empty($email->sessao_id) && isset($sessoes[$email->sessao_id])) {
                $sNome = htmlspecialchars($sessoes[$email->sessao_id] ?: ('#' . $email->sessao_id));
                $sLink = '<a href="gerente/' . $email->sessao_id . '">' . $sNome . '</a>';
            } elseif (!empty($email->sessao_id)) {
                $sLink = '<span class="text-muted">#' . $email->sessao_id . '</span>';
            } else {
                $sLink = '<span class="text-muted">Sem sessão</span>';
            }

            $acoes = '<button type="button" class="btn btn-sm btn-outline-primary btn-ver-email" ' .
                     'data-id="' . $email->id . '" ' .
                     'data-destinatario="' . htmlspecialchars($dest, ENT_QUOTES) . '" ' .
                     'data-assunto="' . htmlspecialchars($assunto, ENT_QUOTES) . '" ' .
                     'data-enviado="' . htmlspecialchars($enviado, ENT_QUOTES) . '" ' .
                     'title="Visualizar e-mail">' .
                     '<i class="fas fa-eye"></i> Ver' .
                     '</button>';

            $rows[] = [
                'id' => (int)$email->id,
                'status_badge' => $statusBadge,
                'enviado' => $enviado,
                'destinatario' => htmlspecialchars($dest),
                'assunto' => htmlspecialchars($assunto),
                'sessao_link' => $sLink,
                'acoes' => $acoes,
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'total' => $total,
            'rows' => $rows,
        ]);
        exit;
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
