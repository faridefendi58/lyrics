<?php
namespace ExtensionsModel;

use Model\R;

require_once __DIR__ . '/../../../models/base.php';

class SongAlbumModel extends \Model\BaseModel
{
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'ext_song_albums';
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
        $items = [];
        if (isset($data['title']))
            $items[0] = $data['title'];

        $models = self::getRows([]);
        foreach ($models as $i => $model) {
            $items[$model['id']] = $model['album_name'];
        }

        return $items;
    }
}
