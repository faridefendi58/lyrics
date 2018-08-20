<?php

namespace Extensions\Controllers;

use Components\BaseController as BaseController;

class ArtistController extends BaseController
{
    public function __construct($app, $user)
    {
        parent::__construct($app, $user);
    }

    public function register($app)
    {
        $app->map(['GET'], '/view', [$this, 'view']);
        $app->map(['GET', 'POST'], '/update/[{id}]', [$this, 'update']);
        $app->map(['GET', 'POST'], '/detail/[{id}]', [$this, 'detail']);
        $app->map(['POST'], '/delete/[{id}]', [$this, 'delete']);
    }

    public function accessRules()
    {
        return [
            ['allow',
                'actions' => ['view', 'update', 'delete', 'detail'],
                'users'=> ['@'],
            ],
            ['deny',
                'users' => ['*'],
            ],
        ];
    }

    public function view($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        $model = new \ExtensionsModel\SongArtistModel();

        return $this->_container->module->render($response, 'songs/artist_view.html', [
            'model' => $model,
            'rows' => $model->getRows()
        ]);
    }

    public function update($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        if (empty($args['id']))
            return false;

        $model = \ExtensionsModel\SongArtistModel::model()->findByPk($args['id']);

        if (isset($_POST['Artis'])){
            $model->name = $_POST['Artis']['name'];
            $model->updated_at = date('Y-m-d H:i:s');
            $update = \ExtensionsModel\SongArtistModel::model()->update($model);
            if ($update) {
                $message = 'Your data is successfully updated.';
                $success = true;
            } else {
                $message = 'Failed to update your lyric.';
                $success = false;
            }
        }

        return $this->_container->module->render($response, 'artists/update.html', [
            'model' => $model,
            'message' => ($message) ? $message : null,
            'success' => $success,
        ]);
    }

    public function delete($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        if (!isset($args['id'])) {
            return false;
        }

        $model = \ExtensionsModel\SongArtistModel::model()->findByPk($args['id']);
        $delete = \ExtensionsModel\SongArtistModel::model()->delete($model);
        if ($delete) {
            $songs = \ExtensionsModel\SongModel::model()->findAllByAttributes(['artist_id' => $args['id']]);
            foreach ($songs as $song) {
                $delete2 = \ExtensionsModel\SongLyricRefferenceModel::model()->deleteAllByAttributes(['song_id' => $song->id]);
                $delete3 = \ExtensionsModel\SongCordRefferenceModel::model()->deleteAllByAttributes(['song_id' => $song->id]);
                $delete4 = \ExtensionsModel\SongModel::model()->delete($song);
            }
            $message = 'Your data is successfully deleted.';
            echo true;
        }
    }

    public function detail($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if (!$isAllowed) {
            return $this->notAllowedAction();
        }

        if (empty($args['id']))
            return false;

        $model = \ExtensionsModel\SongArtistModel::model()->findByPk($args['id']);
        if (!$model instanceof \RedBeanPHP\OODBBean) {
            return false;
        }

        $lmodel = new \ExtensionsModel\SongLyricRefferenceModel();
        $lyrics = $lmodel->getLyrics(['artist_id' => $model->id]);

        $cmodel = new \ExtensionsModel\SongCordRefferenceModel();
        $chords = $cmodel->getChords(['artist_id' => $model->id]);

        return $this->_container->module->render($response, 'songs/artist_detail.html', [
            'model' => $model,
            'smodel' => new \ExtensionsModel\SongModel(),
            'lyrics' => $lyrics,
            'chords' => $chords
        ]);
    }
}