<?php
namespace ExtensionsModel;

use Model\R;

require_once __DIR__ . '/../../../models/base.php';

class SongCordRefferenceModel extends \Model\BaseModel
{
    const STATUS_PENDING = 'pending';
    const STATUS_EXECUTED = 'executed';
    const STATUS_CANCELED = 'canceled';
    const STATUS_APPROVED = 'approved';

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'ext_song_chord_refferences';
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

    public function getChords($data) {
        $sql = "SELECT t.*, s.title AS song_title, s.slug AS song_slug, s.status AS song_status,
        a.name AS artist_name, a.slug AS artist_slug   
        FROM {tablePrefix}ext_song_chord_refferences t 
        LEFT JOIN {tablePrefix}ext_song s ON s.id = t.song_id 
        LEFT JOIN {tablePrefix}ext_song_artists a ON a.id = s.artist_id ";

        $sql .= " WHERE 1";

        $params = array();

        if (isset($data['artist_id'])) {
            $sql .= ' AND s.artist_id =:artist_id';
            $params['artist_id'] = $data['artist_id'];
        }

        if (isset($data['limit']))
            $sql .= ' LIMIT '.$data['limit'];

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql, $params );

        return $rows;
    }
}
