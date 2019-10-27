<?php
namespace ExtensionsModel;

use Model\R;

require_once __DIR__ . '/../../../models/base.php';

class SongLyricRefferenceModel extends \Model\BaseModel
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
        return 'ext_song_lyric_refferences';
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

    public function getLyrics($data) {
        $sql = "SELECT t.*, s.title AS song_title, s.slug AS song_slug, s.status AS song_status,
        a.name AS artist_name, a.slug AS artist_slug   
        FROM {tablePrefix}ext_song_lyric_refferences t 
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

    public function recordVisit($slug, $song_id = 0)
    {
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $must_save = false;

        $delay = 60*5; // 5 minutes

        if (!empty($_SESSION[$slug][$ip_address])) {
            if (time() > $_SESSION[$slug][$ip_address]) {
                $_SESSION[$slug][$ip_address] = time() + $delay;
                $must_save = true;
            }
        } else {
            $_SESSION[$slug][$ip_address] = time() + $delay;
            $must_save = true;
        }

        if ($must_save) {
            $sql = "UPDATE {tablePrefix}ext_song_lyric_refferences t 
              SET t.viewed = t.viewed + 1 ";

            if ($song_id > 0) {
                $sql .= " WHERE t.song_id =:song_id";
                $params = [ 'song_id' => $song_id ];
            } else {
                $sql .= " WHERE t.permalink =:permalink";
                $params = [ 'permalink' => $slug ];
            }

            $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);
            try {
                $exec = \Model\R::exec( $sql, $params );
            } catch (\Exception $exception) {
            }
        }

        return true;
    }
}
