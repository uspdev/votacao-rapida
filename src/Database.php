<?php namespace Uspdev\Votacao;

use \RedBeanPHP\R as R;

class Database
{

    public static function open()
    {
        if (!R::hasDatabase('votacao')) {
            R::addDatabase('votacao', 'sqlite:' . getenv('USPDEV_VOTACAO_LOCAL') . '/votacao.db3');
        }

        R::selectDatabase('votacao');
        R::useFeatureSet('novice/latest');
        R::freeze(false);
    }

    public static function close()
    {
        R::close();
    }

    // lista com possibilidade de filtro (AND) e busca (OR && LIKE)
    public static function list(string $table, array $filtro = [], array $busca = [], array $tipo = []) {
        list($query, $param) = SELF::criaFiltroBusca($filtro, $busca, $tipo);

        SELF::open();
        $list = R::find($table, $query, $param);
        SELF::close();

        // array_walk($list, function (&$item) use ($table) {
        //     $item->_url = API_URL . '/' . $table . '/' . $item->id;
        // });

        return $list;
    }

    public static function dump($list) {
        SELF::open();
        $res = R::exportAll($list);
        SELF::close();
        return $res;
    }

    // Contar
    public static function count(string $table)
    {
        return SELF::open() && R::count($table) && SELF::close();
    }

    // carregar um registro
    public static function get(string $table, int $id)
    {
        global $ownList;
        $owns = [];
        $parents = [];
        foreach ($ownList as $assoc) {
            list($a, $b) = explode('_', $assoc);
            if ($a == $table) {
                $owns[] = $b;
            }
            if ($b == $table) {
                $parents[] = $a;
            }
        }

        SELF::open();
        $ret = R::load($table, $id);
        SELF::close();

        foreach ($parents as $parent) {
            $ret->$parent;
        }
        foreach ($owns as $own) {
            $str = '_' . $own . '_url';
            $ret->$str = API_URL . '/' . $own . '?f[' . $table . '_id]=' . $ret->id;
        }
        return $ret;
    }

    // inserir novo registro
    public static function put(string $table, $data)
    {
        $data['_type'] = $table;

        SELF::open();
        $id = R::store(R::dispense($data));
        $res = R::load($table, $id);
        SELF::close();

        return $res;
    }

    // atualizar registro existente
    public static function post(string $table, $data)
    {
        SELF::open();
        $res = R::load($table, $data['id']);
        $res->import($data);
        R::store($res);
        SELF::close();

        return $res->fresh();
    }

    // remover registro existente
    public static function delete(string $table, Int $id): Bool
    {
        SELF::open();
        R::trashBatch($table, [$id]);
        $res = R::load($table, $id);
        SELF::close();

        return ($res->id == 0) ? true : false;
    }

    // limpar tudo de uma tabela (usado inicialmente para testes)
    public static function wipe(string $table): Bool
    {
        SELF::open();
        R::wipe($table);
        $res = R::findAll($table);
        SELF::close();
        
        return empty($res) ? true : false;
    }

    public static function criaFiltroBusca(array $filtros, array $buscas, array $tipos)
    {
        //Tipos: PDO::PARAM_INT, PDO::PARAM_STR, etc
        // Abre o parênteses dos filtros
        $str_where = "";
        $params = [];
        if (!empty($filtros) && (count($filtros) > 0)) {
            $str_where .= "(";
            foreach ($filtros as $coluna => $valor) {
                $str_where .= " {$coluna} = ? ";
                if (array_key_exists($coluna, $tipos)) {
                    $params[] = [$valor, $tipos[$coluna]];
                } else {
                    $params[] = $valor;
                }
                // Enquanto existir um filtro, adiciona o operador AND
                if (next($filtros)) {
                    $str_where .= 'AND';
                }
            }
        }
        if (!empty($buscas) && (count($buscas) > 0)) {
            // Caso exista um campo para busca, fecha os parênteses anterior
            // e adiciona mais um AND (, que conterá os parâmetros de busca (OR)
            if (!empty($str_where)) {
                $str_where .= ') AND (';
            } else {
                // Caso não tenha nenhum filtro anterior, adiciona o WHERE
                $str_where .= "(";
            }
            foreach ($buscas as $coluna => $valor) {
                $str_where .= " {$coluna} LIKE ? ";
                if (array_key_exists($coluna, $tipos)) {
                    $params[] = ["%{$valor}%", $tipos[$coluna]];
                } else {
                    $params[] = "%{$valor}%";
                }
                // Enquanto existir uma busca, adiciona o operador OR
                if (next($buscas)) {
                    $str_where .= 'OR';
                } else {
                    // Fecha o parênteses do OR
                    $str_where .= ')';
                }
            }
        } else {
            // Fecha o parênteses dos filtros, caso tenha sido aberto
            if (!empty($str_where)) {
                $str_where .= ')';
            }
        }
        return [$str_where, $params];
    }
}
