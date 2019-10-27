<?php

namespace Extensions\Controllers;

use Components\BaseController as BaseController;

class GenresController extends BaseController
{
    public function __construct($app, $user)
    {
        parent::__construct($app, $user);
    }

    public function register($app)
    {
        $app->map(['GET'], '/view', [$this, 'view']);
        $app->map(['GET', 'POST'], '/create', [$this, 'create']);
        $app->map(['GET', 'POST'], '/update/[{id}]', [$this, 'update']);
        $app->map(['POST'], '/delete/[{id}]', [$this, 'delete']);
    }

    public function accessRules()
    {
        return [
            ['allow',
                'actions' => [
                    'view', 'create', 'update', 'delete'
                ],
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

        $model = new \ExtensionsModel\SongGenreModel();
        $datas = $model->getItems();

        return $this->_container->module->render($response, 'songs/genre_view.html', [
            'datas' => $datas
        ]);
    }

    public function create($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        $message = null; $success = false;
        if (isset($_POST['SongGenre'])){
            $model = new \ExtensionsModel\SongGenreModel('create');
            $model->title = $_POST['SongGenre']['title'];
            $model->slug = \ExtensionsModel\PostModel::createSlug($model->title);
            $model->description = $_POST['SongGenre']['description'];
            $model->status = $_POST['SongGenre']['status'];
            $model->created_at = date('Y-m-d H:i:s');
            $save = \ExtensionsModel\SongGenreModel::model()->save(@$model);
            if ($save) {
                $uploadfile = null;
                if (isset($_FILES['SongGenre'])) {
                    $path_info = pathinfo($_FILES['SongGenre']['name']['image']);
                    if (in_array($path_info['extension'], ['jpg','JPG','jpeg','JPEG','png','PNG'])) {
                        $upload_folder = 'uploads/songs';
                        $file_name = time().'.'.$path_info['extension'];
                        $uploadfile = $upload_folder . '/' . $file_name;
                        try {
                            $upload = move_uploaded_file($_FILES['SongGenre']['tmp_name']['image'], $uploadfile);
                            if ($upload) {
                                if (file_exists($model->image)) {
                                    unlink($model->image);
                                }
                            }
                        } catch (\Exception $e) {}
                    }
                }
                if (!empty($uploadfile)) {
                    $model2 = \ExtensionsModel\SongGenreModel::model()->findByPk($model->id);
                    $model2->image = $uploadfile;
                    $update = \ExtensionsModel\SongGenreModel::model()->update($model2);
                }
                $message = 'Data berhasil disimpan';
                $success = true;
            } else {
                $message = 'Gagal menyimpan data.';
                $success = false;
            }

            return $response->withJson([
                'message' => $message,
                'status' => ($success)? 'success' : 'failed'
            ]);
        }

        return $this->_container->module->render($response, 'songs/genre_create.html', [
            'message' => ($message) ? $message : null,
            'success' => $success
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

        $model = \ExtensionsModel\SongGenreModel::model()->findByPk($args['id']);

        $message = null; $success = false;
        if (isset($_POST['SongGenre'])){
            $model->title = $_POST['SongGenre']['title'];
            $model->slug = \ExtensionsModel\PostModel::createSlug($model->title);
            $model->description = $_POST['SongGenre']['description'];
            $model->status = $_POST['SongGenre']['status'];
            $model->updated_at = date('Y-m-d H:i:s');
            $update = \ExtensionsModel\SongGenreModel::model()->update($model);
            if ($update) {
                $uploadfile = null;
                if (isset($_FILES['SongGenre'])) {
                    $path_info = pathinfo($_FILES['SongGenre']['name']['image']);
                    if (in_array($path_info['extension'], ['jpg','JPG','jpeg','JPEG','png','PNG'])) {
                        $upload_folder = 'uploads/songs';
                        $file_name = time().'.'.$path_info['extension'];
                        $uploadfile = $upload_folder . '/' . $file_name;
                        try {
                            $upload = move_uploaded_file($_FILES['SongGenre']['tmp_name']['image'], $uploadfile);
                            if ($upload) {
                                if (file_exists($model->image)) {
                                    unlink($model->image);
                                }
                            }
                        } catch (\Exception $e) {}
                    }
                }
                if (!empty($uploadfile)) {
                    $model->image = $uploadfile;
                    $update2 = \ExtensionsModel\SongGenreModel::model()->update($model);
                }
                $message = 'Data berhasil diubah';
                $success = true;
            } else {
                $message = 'Gagal merubah data.';
                $success = false;
            }
            return $response->withJson([
                'message' => $message,
                'status' => ($success)? 'success' : 'failed'
            ]);
        }

        return $this->_container->module->render($response, 'songs/genre_update.html', [
            'model' => $model,
            'message' => $message,
            'success' => $success
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

        $model = \ExtensionsModel\SongGenreModel::model()->findByPk($args['id']);
        $count = \ExtensionsModel\SongModel::model()->findAllByAttributes(['genre_id' => $model->id]);
        if (is_array($count) && count($count) == 0) {
            $delete = \ExtensionsModel\SongGenreModel::model()->delete($model);
            if ($delete) {
                $message = 'Data berhasil dihapus.';
                echo true;
                exit;
            }
        } else {
            $model->status = \ExtensionsModel\SongGenreModel::STATUS_DISABLED;
            $model->updated_at = date('Y-m-d H:i:s');
            $update = \ExtensionsModel\SongGenreModel::model()->update($model);
            if ($update) {
                $message = 'Data berhasil dihapus.';
                echo true;
            }
        }
    }
}