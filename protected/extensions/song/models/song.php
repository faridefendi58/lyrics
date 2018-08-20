<?php
namespace ExtensionsModel;

use Model\R;

require_once __DIR__ . '/../../../models/base.php';

class SongModel extends \Model\BaseModel
{
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'ext_song';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return [
            ['title', 'required'],
            ['created_at', 'required', 'on'=>'create'],
        ];
    }

    public function getListStatus()
    {
        return [
            'draft' => self::STATUS_DRAFT,
            'published' => self::STATUS_PUBLISHED,
            'archived' => self::STATUS_ARCHIVED
        ];
    }

    public static function string2array($tags)
    {
        return preg_split('/\s*,\s*/',trim($tags),-1,PREG_SPLIT_NO_EMPTY);
    }

    public static function array2string($tags)
    {
        return implode(', ',$tags);
    }

    public static function createSlug($str)
    {
        $str = strtolower(trim($str));
        $str = preg_replace('/[^a-z0-9-]/', '-', $str);
        $str = preg_replace('/-+/', "-", $str);
        $str = trim($str, '-');
        return $str;
    }

    public function getSongs($data) {
        $sql = "SELECT t.*, a.title AS abjad_name, s.name AS artist_name, s.slug AS artist_slug, g.title AS genre_name, 
        l.url AS lyric_src_url, l.result AS lyric, l.status AS lyric_status, l.section AS lyric_section,
        c.url AS chord_src_url, c.result AS chord, c.status AS chord_status, c.section AS chord_section  
        FROM {tablePrefix}ext_song t 
        LEFT JOIN {tablePrefix}ext_song_artists s ON s.id = t.artist_id 
        LEFT JOIN {tablePrefix}ext_song_abjads a ON a.id = s.abjad_id  
        LEFT JOIN {tablePrefix}ext_song_genres g ON g.id = t.genre_id 
        LEFT JOIN {tablePrefix}ext_song_lyric_refferences l ON l.song_id = t.id  
        LEFT JOIN {tablePrefix}ext_song_chord_refferences c ON c.song_id = t.id  ";

        $sql .= " WHERE 1";

        $params = array();

        if (isset($data['status'])) {
            $sql .= ' AND t.status =:status';
            $params['status'] = $data['status'];
        }

        if (isset($data['status_lyric'])) {
            $sql .= ' AND l.status =:status_lyric';
            $params['status_lyric'] = $data['status_lyric'];
        }

        if (isset($data['artist_id'])) {
            $sql .= ' AND t.artist_id =:artist_id';
            $params['artist_id'] = $data['artist_id'];
        }

        if (isset($data['order_by'])) {
            $sql .= ' ORDER BY '.$data['order_by'].' DESC';
        }

        if (isset($data['limit']))
            $sql .= ' LIMIT '.$data['limit'];

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql, $params );

        return $rows;
    }

    public function getSong($slug)
    {
        $sql = "SELECT t.*, a.title AS abjad_name, s.name AS artist_name, g.title AS genre_name, 
        l.result AS lyric, l.status AS lyric_status, 
        c.result AS chord, c.status AS chord_status    
        FROM {tablePrefix}ext_song t 
        LEFT JOIN {tablePrefix}ext_song_artists s ON s.id = t.artist_id 
        LEFT JOIN {tablePrefix}ext_song_abjads a ON a.id = s.abjad_id 
        LEFT JOIN {tablePrefix}ext_song_genres g ON g.id = t.genre_id 
        LEFT JOIN {tablePrefix}ext_song_lyric_refferences l ON l.song_id = t.id  
        LEFT JOIN {tablePrefix}ext_song_chord_refferences c ON c.song_id = t.id  
        WHERE t.slug =:slug";

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $row = \Model\R::getRow( $sql, ['slug'=>$slug] );

        return $row;
    }

    public function getSongDetail($id)
    {
        $sql = "SELECT t.*, a.title AS abjad_name, s.name AS artist_name, g.title AS genre_name, 
        l.url AS lyric_url, l.result AS lyric, l.status AS lyric_status, l.section AS lyric_section, 
        c.url AS chord_url, c.result AS chord, c.status AS chord_status, c.section AS chord_section      
        FROM {tablePrefix}ext_song t 
        LEFT JOIN {tablePrefix}ext_song_artists s ON s.id = t.artist_id 
        LEFT JOIN {tablePrefix}ext_song_abjads a ON a.id = s.abjad_id 
        LEFT JOIN {tablePrefix}ext_song_genres g ON g.id = t.genre_id   
        LEFT JOIN {tablePrefix}ext_song_lyric_refferences l ON l.song_id = t.id  
        LEFT JOIN {tablePrefix}ext_song_chord_refferences c ON c.song_id = t.id  
        WHERE t.id =:id";

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $row = \Model\R::getRow( $sql, ['id'=>$id] );

        return $row;
    }

    public function getAbjad($title) {
        $sql = "SELECT t.*, a.name AS artist_name     
        FROM {tablePrefix}ext_song_abjads t  
        LEFT JOIN {tablePrefix}ext_song_artists s ON s.id = t.abjad_id 
        WHERE t.title =:title";

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $row = \Model\R::getRow( $sql, ['slug'=>$title] );

        return $row;
    }

    public function getAbjads() {
        $sql = "SELECT t.id, t.title FROM {tablePrefix}ext_song_abjads t WHERE 1";

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql );

        $items = [];
        foreach ($rows as $row) {
            $items[$row['id']] = $row['title'];
        }

        return $items;
    }

    public function buildSongUrl($data) {
        $url = $data['title'];
        if (isset($data['artist'])) {
            $url = $data['artist'].'/'.$url;
        }

        $path = 'lirik';
        if (isset($data['path'])) {
            $path = $data['path'];
        }

        return $path.'/'.$url;
    }

    public function getGenerateResults() {
        $sql = "SELECT t.*, r.status AS ref_status   
          FROM {tablePrefix}ext_song t 
          LEFT JOIN {tablePrefix}ext_song_lyric_refferences r ON r.song_id = t.id 
          WHERE 1";

        $sql.= ' AND r.status=:ref_status';
        $params = [];
        $params['ref_status'] = \ExtensionsModel\SongLyricRefferenceModel::STATUS_PENDING;

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql, $params );

        return $rows;
    }

    public function getSlugs() {
        $sql = "SELECT t.slug FROM {tablePrefix}ext_song t WHERE 1";

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql );

        $items = [];
        foreach ($rows as $row) {
            array_push($items, $row['slug']);
        }

        return $items;
    }

    public function getImage($slug) {
        $file = $_SERVER['DOCUMENT_ROOT'].'/uploads/songs/'.$slug;
        if (file_exists($file.'.jpg')) {
            return 'uploads/songs/'.$slug.'.jpg';
        } elseif (file_exists($file.'.png')) {
            return 'uploads/songs/'.$slug.'.png';
        }

        return false;
    }

    public function getSearch($data) {
        if (!isset($data['type'])) {
            $sql = "SELECT t.*, s.name AS artist_name, s.slug AS artist_slug, 
            l.result AS lyric, c.result AS chord   
            FROM {tablePrefix}ext_song t 
            LEFT JOIN {tablePrefix}ext_song_artists s ON s.id = t.artist_id 
            LEFT JOIN {tablePrefix}ext_song_lyric_refferences l ON l.song_id = t.id  
            LEFT JOIN {tablePrefix}ext_song_chord_refferences c ON c.song_id = t.id 
            WHERE t.status=:status";
        } else {
            if ($data['type'] == 'lyric') {
                $sql = "SELECT t.*, s.name AS artist_name, s.slug AS artist_slug, 
                l.result AS lyric    
                FROM {tablePrefix}ext_song t 
                LEFT JOIN {tablePrefix}ext_song_artists s ON s.id = t.artist_id 
                LEFT JOIN {tablePrefix}ext_song_lyric_refferences l ON l.song_id = t.id 
                WHERE t.status=:status";
            } elseif ($data['type'] == 'chord') {
                $sql = "SELECT t.*, s.name AS artist_name, s.slug AS artist_slug, 
                c.result AS chord   
                FROM {tablePrefix}ext_song t 
                LEFT JOIN {tablePrefix}ext_song_artists s ON s.id = t.artist_id  
                LEFT JOIN {tablePrefix}ext_song_chord_refferences c ON c.song_id = t.id 
                WHERE t.status=:status";
            }
        }

        $params = ['status' => \ExtensionsModel\SongModel::STATUS_PUBLISHED];

        if (isset($data['q'])) {
            if (!isset($data['type'])) {
                $sql .= ' AND (t.title LIKE "%'.$data['q'].'%" 
                OR s.name LIKE "%'.$data['q'].'%" 
                OR l.result LIKE "%'.$data['q'].'%" 
                OR c.result LIKE "%'.$data['q'].'%")';
            } else {
                if ($data['type'] == 'lyric') {
                    $sql .= ' AND (t.title LIKE "%'.$data['q'].'%" 
                    OR s.name LIKE "%'.$data['q'].'%" 
                    OR l.result LIKE "%'.$data['q'].'%")';
                } elseif ($data['type'] == 'chord') {
                    $sql .= ' AND (t.title LIKE "%'.$data['q'].'%" 
                    OR s.name LIKE "%'.$data['q'].'%" 
                    OR c.result LIKE "%'.$data['q'].'%")';
                }
            }
        }

        if (isset($data['order_by'])) {
            $sql .= ' ORDER BY '.$data['order_by'].' DESC';
        }

        if (isset($data['limit']))
            $sql .= ' LIMIT '.$data['limit'];

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql, $params );

        return $rows;
    }
}
