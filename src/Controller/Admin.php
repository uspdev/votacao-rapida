<?php

namespace Uspdev\Votacao\Controller;

use Uspdev\Votacao\Model\Sessao;
use Uspdev\Votacao\Model\Usuario;
use \RedBeanPHP\R as R;

class Admin
{
    const USER_MODEL = [
        [
            'campo' => 'codpes',
            'display' => 'Número USP',
        ]
    ];

    public function __construct()
    {
        R::selectDatabase('votacao');
    }

    public function listarUsuario()
    {
        $this->isAdmin();
        return R::findAll('usuario', 'ORDER BY lastlogin');
    }

    public function listarSessao()
    {
        $this->isAdmin();
        return Sessao::listar($this->usuario, true);
    }

    protected function isAdmin()
    {
        if (empty($this->usuario)) {
            $this->usuario = Usuario::obter($this->query->codpes);
        }

        if ($this->usuario->admin != 1) {
            echo json_encode(['status' => 'erro', 'msg' => 'Usuário sem privilégios']);
            exit;
        }
    }
}
