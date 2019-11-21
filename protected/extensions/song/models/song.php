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
        $sql = "SELECT t.*, a.title AS abjad_name, s.name AS artist_name, s.slug AS artist_slug, s.song_url, s.song_section, 
        g.id AS genre_id, g.title AS genre_name, l.url AS lyric_src_url, l.result AS lyric, l.status AS lyric_status, l.section AS lyric_section,
        c.url AS chord_src_url, c.result AS chord, c.status AS chord_status, c.section AS chord_section, c.viewed, c.permalink AS chord_permalink  
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

        if (isset($data['status_chord'])) {
            $sql .= ' AND c.status =:status_chord';
            $params['status_chord'] = $data['status_chord'];
        }

        if (isset($data['artist_id'])) {
            $sql .= ' AND t.artist_id =:artist_id';
            $params['artist_id'] = $data['artist_id'];
        }

        if (isset($data['type'])) {
            if ($data['type'] == 'lyric') {
                $sql .= ' AND l.result IS NOT NULL';
                if (isset($data['top_track'])) {
                    $sql .= ' AND l.top_track =:top_track';
                    $params['top_track'] = $data['top_track'];
                }
            }
            if ($data['type'] == 'chord') {
                $sql .= ' AND c.result IS NOT NULL';
                if (isset($data['top_track'])) {
                    $sql .= ' AND c.top_track =:top_track';
                    $params['top_track'] = $data['top_track'];
                }
            }
        }

        if (isset($data['featured'])) {
            if (isset($data['type']) && $data['type'] == 'chord') {
                $sql .= ' AND c.featured = 1';
            } else {
                $sql .= ' AND l.featured = 1';
            }
        }

        if (isset($data['tag'])) {
            if (isset($data['type']) && $data['type'] == 'chord') {
                $sql .= ' AND c.tags LIKE "%'. $data['tag'] .'%"';
            }
        }

        if (isset($data['order_by'])) {
            $sql .= ' ORDER BY '.$data['order_by'].' DESC';
        } else {
            $sql .= ' ORDER BY t.created_at DESC';
        }

        if (isset($data['limit']))
            $sql .= ' LIMIT '.$data['limit'];

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql, $params );

        return $rows;
    }

    public function getSong($slug)
    {
        $sql = "SELECT t.*, a.title AS abjad_name, s.name AS artist_name, s.slug AS artist_slug, 
        g.title AS genre_name, l.result AS lyric, l.status AS lyric_status, 
        c.result AS chord, c.status AS chord_status,
        ab.album_name, ab.release_year, ab.label_name,
        l.permalink AS lyric_permalink, l.meta_title AS lyric_meta_title, l.meta_keyword AS lyric_meta_keyword, l.meta_description AS lyric_meta_description,      
        c.permalink AS chord_permalink, c.meta_title AS chord_meta_title, c.meta_keyword AS chord_meta_keyword, c.meta_description AS chord_meta_description    
        FROM {tablePrefix}ext_song t 
        LEFT JOIN {tablePrefix}ext_song_artists s ON s.id = t.artist_id 
        LEFT JOIN {tablePrefix}ext_song_abjads a ON a.id = s.abjad_id 
        LEFT JOIN {tablePrefix}ext_song_genres g ON g.id = t.genre_id 
        LEFT JOIN {tablePrefix}ext_song_albums ab ON ab.id = t.album_id  
        LEFT JOIN {tablePrefix}ext_song_lyric_refferences l ON l.song_id = t.id  
        LEFT JOIN {tablePrefix}ext_song_chord_refferences c ON c.song_id = t.id  
        WHERE t.slug =:slug";


        $sql .= " OR l.permalink =:slug OR c.permalink =:slug";

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $row = \Model\R::getRow( $sql, ['slug'=>$slug] );

        return $row;
    }

    public function getSongByTitle($title, $artist_slug = null)
    {
        if (strpos($title, "-") !== false) {
            $exp = explode("-", $title);
            $title = implode(" ", $exp);
        }
        $sql = "SELECT t.*, a.title AS abjad_name, s.name AS artist_name, s.slug AS artist_slug, 
        g.title AS genre_name, l.result AS lyric, l.status AS lyric_status, 
        c.result AS chord, c.status AS chord_status,
        ab.album_name, ab.release_year, ab.label_name,
        l.permalink AS lyric_permalink, l.meta_title AS lyric_meta_title, l.meta_keyword AS lyric_meta_keyword, l.meta_description AS lyric_meta_description,      
        c.permalink AS chord_permalink, c.meta_title AS chord_meta_title, c.meta_keyword AS chord_meta_keyword, c.meta_description AS chord_meta_description    
        FROM {tablePrefix}ext_song t 
        LEFT JOIN {tablePrefix}ext_song_artists s ON s.id = t.artist_id 
        LEFT JOIN {tablePrefix}ext_song_abjads a ON a.id = s.abjad_id 
        LEFT JOIN {tablePrefix}ext_song_genres g ON g.id = t.genre_id 
        LEFT JOIN {tablePrefix}ext_song_albums ab ON ab.id = t.album_id  
        LEFT JOIN {tablePrefix}ext_song_lyric_refferences l ON l.song_id = t.id  
        LEFT JOIN {tablePrefix}ext_song_chord_refferences c ON c.song_id = t.id  
        WHERE 1";

        $params = [];
        if (!empty($artist_slug)) {
            $sql .= " AND s.slug =:artist_slug AND LOWER(t.title) LIKE '%".$title."%'";
            $params['artist_slug'] = $artist_slug;
        } else {
            $sql .= " AND t.title =:title";
            $params['title'] = $title;
        }

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $row = \Model\R::getRow( $sql, $params );

        return $row;
    }

    public function getSongDetail($id)
    {
        $sql = "SELECT t.*, a.title AS abjad_name, s.name AS artist_name, s.slug AS artist_slug, g.title AS genre_name, 
        l.url AS lyric_url, l.result AS lyric, l.status AS lyric_status, l.section AS lyric_section, 
        c.url AS chord_url, c.result AS chord, c.status AS chord_status, c.section AS chord_section,
        ab.album_name, ab.release_year, ab.label_name,
        l.permalink AS lyric_permalink, l.meta_title AS lyric_meta_title, l.meta_keyword AS lyric_meta_keyword, l.meta_description AS lyric_meta_description, l.featured, l.top_track,     
        c.permalink AS chord_permalink, c.meta_title AS chord_meta_title, c.meta_keyword AS chord_meta_keyword, c.meta_description AS chord_meta_description, c.featured, c.top_track, c.tags      
        FROM {tablePrefix}ext_song t 
        LEFT JOIN {tablePrefix}ext_song_artists s ON s.id = t.artist_id 
        LEFT JOIN {tablePrefix}ext_song_abjads a ON a.id = s.abjad_id 
        LEFT JOIN {tablePrefix}ext_song_genres g ON g.id = t.genre_id  
        LEFT JOIN {tablePrefix}ext_song_albums ab ON ab.id = t.album_id   
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
        if (!array_key_exists('permalink', $data) && array_key_exists('title', $data)) {
            $url = $data['title'];
            $song = self::getSong($data['title']);
        } else {
            $url = $data['permalink'];
            $song = null;
        }

        if (isset($data['artist'])) {
            $url = $data['artist'].'/'.$url;
        }

        $path = 'lirik';
        if (isset($data['path'])) {
            $path = $data['path'];
        }

        switch ($path) {
            case 'lirik':
                if (is_array($song) && isset($song['lyric_permalink'])) {
                    $url = $data['artist'].'/'.$song['lyric_permalink'];
                }
                break;
            case 'kord':
                if (is_array($song) && isset($song['chord_permalink'])) {
                    $url = $data['artist'].'/'.$song['chord_permalink'];
                }
                break;
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
        if (file_exists($file.'.webp')) {
            return 'uploads/songs/'.$slug.'.webp';
        } elseif (file_exists($file.'.jpg')) {
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
                WHERE t.status=:status l.id IS NOT NULL";
            } elseif ($data['type'] == 'chord') {
                $sql = "SELECT t.*, s.name AS artist_name, s.slug AS artist_slug, 
                c.result AS chord, c.permalink AS chord_permalink, g.title AS genre_name   
                FROM {tablePrefix}ext_song t 
                LEFT JOIN {tablePrefix}ext_song_artists s ON s.id = t.artist_id  
                LEFT JOIN {tablePrefix}ext_song_chord_refferences c ON c.song_id = t.id 
                LEFT JOIN {tablePrefix}ext_song_genres g ON g.id = t.genre_id 
                WHERE t.status=:status AND c.id IS NOT NULL";
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

    public function getArtists($data=null) {
        $sql = "SELECT t.name AS artist_name, t.slug AS artist_slug, COUNT(s.id) AS tot_song, 
          COUNT(c.id) AS tot_chord, COUNT(l.id) AS tot_lyric  
          FROM {tablePrefix}ext_song s 
          LEFT JOIN {tablePrefix}ext_song_artists t ON t.id = s.artist_id 
          LEFT JOIN {tablePrefix}ext_song_chord_refferences c ON c.song_id = s.id 
          LEFT JOIN {tablePrefix}ext_song_lyric_refferences l ON l.song_id = s.id 
          WHERE 1";

        $params = [];
        if ($data['abjad_id']) {
            $sql .= ' AND t.abjad_id =:abjad_id';
            $params['abjad_id'] = $data['abjad_id'];
        }

        if ($data['has_chord']) {
            $sql .= ' AND c.id IS NOT NULL AND c.status ="approved"';
        }

        if ($data['has_lyric']) {
            $sql .= ' AND l.id IS NOT NULL';
        }

        if ($data['genre_id']) {
            $sql .= ' AND s.genre_id =:genre_id';
            $params['genre_id'] = $data['genre_id'];
        }

        $sql .= ' GROUP BY s.artist_id';

        if ($data['has_song']) {
            $sql .= ' HAVING tot_song > 0';
        }

        if ($data['order_by']) {
            $sql .= ' ORDER BY t.'. $data['order_by'];
        }

        if ($data['limit']) {
            $sql .= ' LIMIT '. $data['limit'];
        }

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql, $params );

        return $rows;
    }

    /**
     * List song title with singer name
     * @param $data
     * @return array
     */
    public function getSongsWithArtist($data) {
        $rows = self::getSongs($data);
        $items = [];
        if (is_array($rows) && count($rows)>0) {
            foreach ($rows as $i => $row) {
                $items[$row['artist_name']][] = $row;
            }
        }

        return $items;
    }

    public function getRelateds($data) {
        if (!empty($data['song_id']) && !empty($data['type'])) {
            $sql = '';
            if ($data['type'] == 'chord') {
                $sql .= "SELECT t.*, s.title, s.slug, 
                a.name AS artist_name, a.slug AS artist_slug, s.published_at   
                FROM {tablePrefix}ext_song_chord_refferences t 
                LEFT JOIN {tablePrefix}ext_song s ON s.id = t.song_id
                LEFT JOIN {tablePrefix}ext_song_artists a ON a.id = s.artist_id";
            } elseif ($data['type'] == 'lyric') {
                $sql .= "SELECT t.*, s.title, s.slug, 
                a.name AS artist_name, a.slug AS artist_slug, s.published_at 
                FROM {tablePrefix}ext_song_lyric_refferences t 
                LEFT JOIN {tablePrefix}ext_song s ON s.id = t.song_id
                LEFT JOIN {tablePrefix}ext_song_artists a ON a.id = s.artist_id";
            }

            $sql .= " WHERE t.song_id <>:song_id 
                AND t.status =:approved 
                AND s.artist_id =:artist_id AND s.status =:song_status";
            $sql .= " ORDER BY t.created_at DESC";

            $params = [
                'song_id' => $data['song_id'],
                'approved' => \ExtensionsModel\SongLyricRefferenceModel::STATUS_APPROVED,
                'artist_id' => $data['artist_id'],
                'song_status' => \ExtensionsModel\SongModel::STATUS_PUBLISHED
            ];
            $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

            $rows = \Model\R::getAll( $sql, $params );

            return $rows;
        }

        return false;
    }

    public function getStatistic($data) {
        if (isset($data['type'])) {
            switch ($data['type']) {
                case 'lyric_published_counter':
                    $sql = "SELECT COUNT(t.id) AS counter   
                    FROM {tablePrefix}ext_song_lyric_refferences t  
                    LEFT JOIN {tablePrefix}ext_song s ON s.id = t.song_id 
                    WHERE s.status =:status";

                    $params = ['status' => \ExtensionsModel\SongModel::STATUS_PUBLISHED];
                    break;
                case 'chord_published_counter':
                    $sql = "SELECT COUNT(t.id) AS counter   
                    FROM {tablePrefix}ext_song_chord_refferences t  
                    LEFT JOIN {tablePrefix}ext_song s ON s.id = t.song_id 
                    WHERE s.status =:status";

                    $params = ['status' => \ExtensionsModel\SongModel::STATUS_PUBLISHED];
                    break;
                case 'total_artist':
                    $sql = "SELECT COUNT(t.id) AS counter   
                    FROM {tablePrefix}ext_song_artists t  
                    LEFT JOIN {tablePrefix}ext_song s ON s.artist_id = t.id 
                    WHERE s.status =:status GROUP BY t.id";

                    $params = ['status' => \ExtensionsModel\SongModel::STATUS_PUBLISHED];
                    break;
                case 'total_published_song':
                    $sql = "SELECT COUNT(t.id) AS counter   
                    FROM {tablePrefix}ext_song t 
                    WHERE t.status =:status";

                    $params = ['status' => \ExtensionsModel\SongModel::STATUS_PUBLISHED];
                    break;
            }
            $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

            $row = \Model\R::getRow( $sql, $params );

            return $row['counter'];
        }

        return false;
    }

    public function getVisitorBySegment($data) {
        if (isset($data['type'])) {
            $sql = "SELECT COUNT(t.id) AS counter   
            FROM {tablePrefix}visitor t 
            WHERE t.url LIKE '%".$data['type']."%'";

            $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

            $row = \Model\R::getRow( $sql);

            return $row['counter'];
        }
        return 0;
    }

    public function getSitemaps($data = [])
    {
        $sql = "SELECT t.id, t.slug, a.slug AS artist_slug, ab.title AS abjad_name,
        l.featured AS lyric_featured, c.featured AS chord_featured,
        l.updated_at AS last_lyric_update, c.updated_at AS last_chord_update,
        l.permalink AS lyric_permalink, c.permalink AS chord_permalink
        FROM {tablePrefix}ext_song t
        LEFT JOIN {tablePrefix}ext_song_artists a ON a.id = t.artist_id
        LEFT JOIN {tablePrefix}ext_song_abjads ab ON ab.id = a.abjad_id
        LEFT JOIN {tablePrefix}ext_song_chord_refferences c ON c.song_id = t.id
        LEFT JOIN {tablePrefix}ext_song_lyric_refferences l ON l.song_id = t.id
        WHERE t.status =:status";

        $params = [ 'status' => self::STATUS_PUBLISHED ];

        $sql .= " ORDER BY a.name ASC";

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql, $params );
        $items = [];
        if (count($rows) > 0) {
            $tool = new \Components\Tool();
            $url_origin = $tool->url_origin();
            $artists = []; $abjads = [];
            $artist_chords = []; $abjad_chords = [];
            foreach ($rows as $i => $row) {
                // hide the abjad category
                /*if (!in_array($row['abjad_name'], $abjads)) {
                    $items[] = [
                        'loc' => $url_origin.'/lirik/'.$row['abjad_name'],
                        'lastmod' => date("c"),
                        'priority' => 0.5
                    ];
                    array_push($abjads, $row['abjad_name']);
                }*/
                if (!in_array($row['artist_slug'], $artists)) {
                    $items[] = [
                        'loc' => $url_origin.'/lirik/'.$row['artist_slug'],
                        'lastmod' => date("c"),
                        'priority' => 0.5
                    ];
                    array_push($artists, $row['artist_slug']);
                }
                $items[] = [
                    'loc' => $url_origin.'/'.$this->buildSongUrl(['path' => 'lirik', 'artist' => $row['artist_slug'], 'permalink' => $row['lyric_permalink']]),
                    'lastmod' => date("c", strtotime($row['last_lyric_update'])),
                    'priority' => ($row['lyric_featured'] > 0)? 0.6 : 0.5
                ];
                if (!empty($row['last_chord_update'])) {
                    /*if (!in_array($row['abjad_name'], $abjad_chords)) {
                        $items[] = [
                            'loc' => $url_origin.'/kord/'.$row['abjad_name'],
                            'lastmod' => date("c"),
                            'priority' => 0.5
                        ];
                        array_push($abjad_chords, $row['abjad_name']);
                    }*/
                    if (!in_array($row['artist_slug'], $artist_chords)) {
                        $items[] = [
                            'loc' => $url_origin.'/kord/'.$row['artist_slug'],
                            'lastmod' => date("c"),
                            'priority' => 0.5
                        ];
                        array_push($artist_chords, $row['artist_slug']);
                    }
                    $items[] = [
                        'loc' => $url_origin.'/'.$this->buildSongUrl(['path' => 'kord', 'artist' => $row['artist_slug'], 'permalink' => $row['chord_permalink']]),
                        'lastmod' => date("c", strtotime($row['last_chord_update'])),
                        'priority' => ($row['chord_featured'] > 0)? 0.6 : 0.5
                    ];
                }
            }
        }

        return $items;
    }

    public function getMedia($data = []) {
        $sql = "SELECT m.mp3_url, m.video_url   
        FROM {tablePrefix}ext_song t 
        LEFT JOIN {tablePrefix}ext_song_media_refferences m ON m.song_id = t.id";

        $sql .= " WHERE m.status =:status";

        $params = ['status' => \ExtensionsModel\SongMediaRefferenceModel::STATUS_ENABLED];

        if (isset($data['song_id'])) {
            $sql .= " AND m.song_id =:song_id";
            $params['song_id'] = $data['song_id'];
        }

        if (isset($data['limit']))
            $sql .= ' LIMIT '.$data['limit'];

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $row = \Model\R::getRow( $sql, $params );

        return $row;
    }

    public function cleanChordFormat($content) {
        $content = str_replace(array( '[', ']' ), array('%', '%'), $content);
        $content = str_replace(array( '{', '}' ), '', $content);
        $content = trim(preg_replace('/\s*\%[^%]*\%/', '', $content));
        return $content;
    }

    public function reformatChordContent($content) {
        $content = str_replace(array( '[', ']' ), array('<sup><a href="#" class="chord">', '</a></sup>'), $content);
        $content = str_replace(array( '{', '}' ), array('<a href="#" class="chord">', '</a>'), $content);

        return $content;
    }

    public function getLyricFormatFromChord($content) {
        if (strpos($content, "{") !== false) {
            $content = str_replace(array( '{', '}', ':', '[', ']' ), array('%', '%', '', '%', '%'), $content);
            $content = trim(preg_replace('/\s*\%[^%]*\%/', '', $content));
            $content = strstr($content, "Intro");
            $content = str_replace(array( 'Intro', 'Musik', 'Reff', 'Outro' ), '', $content);
            $content = preg_replace('#<br />(\s*<br />)+#', ',', $content);
        }
        $content = preg_replace('#<p[^>]*>(\s|&nbsp;?)*</p>#', '', $content);
        if (strpos($content, "x)")) {
            $content = str_replace(array('(2x)', '(3x)', '(4x)'), '', $content);
        }
        $content = preg_replace('#<br[^>]*>#', ' ', $content);

        return $content;
    }
}
