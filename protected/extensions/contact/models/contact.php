<?php
namespace ExtensionsModel;

require_once __DIR__ . '/../../../models/base.php';

class ContactModel extends \Model\BaseModel
{
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'ext_contact';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return [
            ['name, email, message', 'required'],
        ];
    }

    public function getItems($data = []) {
        $sql = "SELECT t.* 
                FROM {tablePrefix}ext_contact t 
                WHERE 1";

        $sql .= ' ORDER BY t.created_at DESC';

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql );

        return $rows;
    }
}
