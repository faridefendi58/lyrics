<?php
namespace ExtensionsModel;

use Model\R;

require_once __DIR__ . '/../../../models/base.php';

class SongMediaRefferenceModel extends \Model\BaseModel
{
    const STATUS_ENABLED = 'enabled';
    const STATUS_DISABLED = 'disabled';

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'ext_song_media_refferences';
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

    public function getMedia($data = []) {
        $sql = "SELECT t.*   
        FROM {tablePrefix}ext_song_media_refferences t 
        LEFT JOIN {tablePrefix}ext_song s ON s.id = t.song_id";

        $sql .= " WHERE t.status =:status";

        $params = ['status' => self::STATUS_ENABLED];

        if (isset($data['song_id'])) {
            $sql .= " AND t.song_id =:song_id";
            $params['song_id'] = $data['song_id'];
        }

        if (isset($data['limit']))
            $sql .= ' LIMIT '.$data['limit'];

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $row = \Model\R::getRow( $sql, $params );

        return $row;
    }
}
