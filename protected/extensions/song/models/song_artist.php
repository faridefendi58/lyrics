<?php
namespace ExtensionsModel;

use Model\R;

require_once __DIR__ . '/../../../models/base.php';

class SongArtistModel extends \Model\BaseModel
{
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'ext_song_artists';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return [
            ['name', 'required'],
            ['created_at', 'required', 'on'=>'create'],
        ];
    }

    public function getSlugs() {
        $sql = "SELECT t.slug FROM {tablePrefix}ext_song_artists t WHERE 1";

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql );

        $items = [];
        foreach ($rows as $row) {
            array_push($items, $row['slug']);
        }

        return $items;
    }

    public function getGenerateResults($data = null) {
        $sql = "SELECT t.*, s.id AS song_id, a.title AS abjad, COUNT(s.id) AS tot_song   
          FROM {tablePrefix}ext_song_artists t 
          LEFT JOIN {tablePrefix}ext_song s ON s.artist_id = t.id 
          LEFT JOIN {tablePrefix}ext_song_abjads a ON a.id = t.abjad_id 
          WHERE 1";

        $params = [];
        if (isset($data['abjad_id'])) {
            $sql .= " AND t.abjad_id =:abjad_id";
            $params['abjad_id'] = $data['abjad_id'];
        }

        $sql.= " GROUP BY t.name";

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql, $params );

        return $rows;
    }

    public function getArtistsWithAbjad($data = array()) {
        $sql = "SELECT t.*, a.title AS abjad    
          FROM {tablePrefix}ext_song_artists t 
          LEFT JOIN {tablePrefix}ext_song_abjads a ON a.id = t.abjad_id 
          WHERE 1";

        $params = [];
        if (isset($data['abjad_id'])) {
            $sql .= " AND t.abjad_id =:abjad_id";
            $params['abjad_id'] = $data['abjad_id'];
        }

        $sql.= " GROUP BY t.name";

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql, $params );

        $items = [];
        if (is_array($rows) && count($rows)>0) {
            foreach ($rows as $i => $row) {
                $items[$row['abjad']][] = $row;
            }
        }

        return $items;
    }
}
