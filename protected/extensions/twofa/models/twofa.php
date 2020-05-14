<?php
namespace ExtensionsModel;

require_once __DIR__ . '/../../../models/base.php';

class TwofaModel extends \Model\BaseModel
{
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'ext_twofa';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return [
            ['admin_id', 'required'],
        ];
    }

    public function getItems($data = []) {
        $sql = "SELECT a.id, a.username, a.email, a.salt, t.status AS twofa_status, a.created_at 
                FROM {tablePrefix}admin a
                LEFT JOIN {tablePrefix}ext_twofa t ON t.admin_id = a.id 
                WHERE 1";

        $sql .= ' ORDER BY a.created_at DESC';

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql );

        return $rows;
    }

    public function getItem($id) {
        $sql = "SELECT a.id, a.username, a.email, a.salt, t.status AS twofa_status, a.created_at 
                FROM {tablePrefix}admin a
                LEFT JOIN {tablePrefix}ext_twofa t ON t.admin_id = a.id 
                WHERE a.id =:id";

        $params = ['id' => $id];

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $row = \Model\R::getRow( $sql, $params );

        return $row;
    }
}
