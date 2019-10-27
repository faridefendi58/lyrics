<?php
namespace ExtensionsModel;

use Model\R;

require_once __DIR__ . '/../../../models/base.php';

class SongRequestModel extends \Model\BaseModel
{
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'ext_song_requests';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return [
            ['created_at', 'required', 'on'=>'create'],
        ];
    }

    public function getItems($data = array())
    {
        $sql = "SELECT t.*     
        FROM {tablePrefix}ext_song_requests t 
        WHERE 1";

        $params = [];
        if (is_array($data) && isset($data['status'])) {
            $sql .= " AND t.status =:status";
            $params['status'] = $data['status'];
        }

        $sql .= " ORDER BY t.created_at DESC";

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql, $params );

        return $rows;
    }
}
