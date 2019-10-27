<?php
namespace ExtensionsModel;

use Model\R;

require_once __DIR__ . '/../../../models/base.php';

class SongGenreModel extends \Model\BaseModel
{
    const STATUS_ENABLED= 'enabled';
    const STATUS_DISABLED = 'disabled';

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'ext_song_genres';
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

    public function getItems($data = []) {
        $sql = "SELECT t.*, (SELECT COUNT(s.id) as count FROM {tablePrefix}ext_song s WHERE s.genre_id = t.id) AS song_counter 
            FROM {tablePrefix}ext_song_genres t 
            WHERE 1";

        $params = [];
        if (isset($data['status'])) {
            $sql .= ' AND t.status =:status';
            $params['status'] = $data['status'];
        }

        if (isset($data['has_song_only'])) {
            $sql .= ' HAVING song_counter > 0';
        }

        if (isset($data['order_by'])) {
            $sql .= ' ORDER BY t.'. $data['order_by'].' ASC';
        } else {
            $sql .= ' ORDER BY t.title ASC';
        }

        if (isset($data['limit'])) {
            $sql .= ' LIMIT '. $data['limit'];
        }

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql, $params );

        return $rows;
    }

    public function getStatuses($status = null)
    {
        $items = [
            self::STATUS_ENABLED => 'Enabled',
            self::STATUS_DISABLED => 'Disabled'
        ];

        if (!empty($status)) {
            return $items[$status];
        }

        return $items;
    }

    public function getSitemaps($data = [])
    {
        $items = self::getItems(['status' => self::STATUS_ENABLED, 'has_song_only' => true]);

        $tool = new \Components\Tool();
        $url_origin = $tool->url_origin();

        $gens = []; $slgs = [];
        foreach ($items as $i => $item) {
            if (!empty($item['slug']) && !in_array($item['slug'], $slgs)) {
                $gens[] = [
                    'loc' => $url_origin.'/genre/'.$item['slug'],
                    'lastmod' => date("c", strtotime($item['updated_at'])),
                    'priority' => 0.5
                ];
                array_push($slgs, $item['slug']);
            }
        }

        return $gens;
    }
}
