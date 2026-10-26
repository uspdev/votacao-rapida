<?php

namespace Uspdev\Votacao\Controller;

use Uspdev\Votacao\Model\Sessao;
use Uspdev\Votacao\Model\Usuario;
use \RedBeanPHP\R as R;

#[\AllowDynamicProperties]
class Admin
{
    /**
     * Parâmetros query passados via GET pelo Webservice.
     * @var \flight\util\Collection|null
     */
    public $query;

    /**
     * Dados enviados via POST pelo Webservice.
     * @var \flight\util\Collection|null
     */
    public $data;

    /**
     * Método HTTP da requisição (GET, POST, etc.).
     * @var string|null
     */
    public $method;

    /**
     * Arquivos enviados na requisição pelo Webservice.
     * @var \flight\util\Collection|null
     */
    public $files;

    /**
     * Usuário administrador autenticado.
     * @var \RedBeanPHP\OODBBean|null
     */
    public $usuario;

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
