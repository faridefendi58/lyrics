<?php

namespace Extensions\Controllers;

use Components\BaseController as BaseController;

class ImagesController extends BaseController
{
    public function __construct($app, $user)
    {
        parent::__construct($app, $user);
    }

    public function register($app)
    {
        $app->map(['GET'], '/artists', [$this, 'artists']);
        $app->map(['GET'], '/chords', [$this, 'chords']);
        $app->map(['GET', 'POST'], '/upload-artist', [$this, 'upload_artist']);
        $app->map(['GET', 'POST'], '/upload-chord', [$this, 'upload_chord']);
        $app->map(['GET', 'POST'], '/update-artist/[{id}]', [$this, 'update_artist']);
        $app->map(['GET', 'POST'], '/update-chord/[{id}]', [$this, 'update_chord']);
        $app->map(['POST'], '/delete-artist/[{id}]', [$this, 'delete_artist']);
        $app->map(['POST'], '/delete-chord/[{id}]', [$this, 'delete_chord']);
    }

    public function accessRules()
    {
        return [
            ['allow',
                'actions' => [
                    'artists', 'chords', 'upload-artist', 'upload-chord',
                    'update-artist', 'update-chord',
                    'delete-artist', 'delete-chord'
                ],
                'users'=> ['@'],
            ],
            ['deny',
                'users' => ['*'],
            ],
        ];
    }

    public function chords($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        $datas = [];
        foreach(glob( $this->_settings['basePath'] . '/../uploads/chords/*.webp') as $chord) {
            if (file_exists($chord)) {
                $pathinfo = pathinfo($chord);
                $pathinfo['folder_name'] = 'uploads/chords/'. $pathinfo['basename'];
                $pathinfo['chord_name'] = $pathinfo['filename'];
                if (strpos($pathinfo['filename'], "-") !== false) {
                    $pathinfo['chord_name'] = str_replace("-", "/", $pathinfo['chord_name']);
                }
                if (strpos($pathinfo['filename'], "is") !== false) {
                    $pathinfo['chord_name'] = str_replace("is", "#", $pathinfo['chord_name']);
                }
                $pathinfo['last_modified'] = date ("Y-m-d H:i:s.", filemtime($chord));
                $datas[] = $pathinfo;
            }
        }

        return $this->_container->module->render($response, 'songs/image_chords.html', [
            'datas' => $datas
        ]);
    }

    public function upload_chord($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        $message = null; $success = false;
        if (isset($_POST['Chords']) && isset($_FILES['Chords'])){
            if (isset($_FILES['Chords']) && !empty($_FILES['Chords']['name']['image'])) {
                $path_info = pathinfo($_FILES['Chords']['name']['image']);
                if (in_array($path_info['extension'], ['webp'])) {
                    $upload_folder = 'uploads/chords';
                    $file_name = $path_info['basename'];
                    if ($path_info['filename'] != $_POST['Chords']['title']) {
                        $title = $_POST['Chords']['title'];
                        if (strpos($title, "/") !== false) {
                            $title = str_replace("/", "-", $title);
                        }
                        if (strpos($title, "#") !== false) {
                            $title = str_replace("#", "is", $title);
                        }
                        $file_name = $title.'.'.$path_info['extension'];
                    }
                    $uploadfile = $upload_folder . '/' . $file_name;
                    try {
                        $upload = move_uploaded_file($_FILES['Chords']['tmp_name']['image'], $uploadfile);
                        if ($upload) {
                            $message = $file_name.' berhasil diupload';
                            $success = true;
                        } else {
                            $message = $file_name.' Gagal diupload.';
                            $success = false;
                        }
                    } catch (\Exception $e) {}
                } else {
                    $message = "Hanya file .webp yang diperbolehkan";
                }
            }

            return $response->withJson([
                'message' => $message,
                'status' => ($success)? 'success' : 'failed'
            ]);
        }

        return $this->_container->module->render($response, 'songs/image_upload_chord.html', [
            'message' => ($message) ? $message : null,
            'success' => $success
        ]);
    }

    public function update_chord($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        if (empty($args['id']))
            return false;

        $file = $this->_settings['basePath'] . '/../uploads/chords/'. $args['id'];
        if (!file_exists($file)) {
            return false;
        }
        $data = pathinfo($file);
        $data['folder_name'] = 'uploads/chords/'. $data['basename'];

        $message = null; $success = false;
        if (isset($_POST['Chords']) && isset($_FILES['Chords'])){
            if (isset($_FILES['Chords']) && !empty($_FILES['Chords']['name']['image'])) {
                $path_info = pathinfo($_FILES['Chords']['name']['image']);
                if (in_array($path_info['extension'], ['webp'])) {
                    $upload_folder = 'uploads/chords';
                    $file_name = $path_info['basename'];
                    if ($path_info['filename'] != $_POST['Chords']['title']) {
                        $title = $_POST['Chords']['title'];
                        if (strpos($title, "/") !== false) {
                            $title = str_replace("/", "-", $title);
                        }
                        if (strpos($title, "#") !== false) {
                            $title = str_replace("#", "is", $title);
                        }
                        $file_name = $title.'.'.$path_info['extension'];
                    }
                    $uploadfile = $upload_folder . '/' . $file_name;
                    try {
                        $upload = move_uploaded_file($_FILES['Chords']['tmp_name']['image'], $uploadfile);
                        if ($upload) {
                            unlink($file);
                            $message = $file_name.' berhasil diupload';
                            $success = true;
                        } else {
                            $message = $file_name.' Gagal diupload.';
                            $success = false;
                        }
                    } catch (\Exception $e) {}
                } else {
                    $message = "Hanya file .webp yang diperbolehkan";
                }
            }

            return $response->withJson([
                'message' => $message,
                'status' => ($success)? 'success' : 'failed'
            ]);
        }

        return $this->_container->module->render($response, 'songs/image_update_chord.html', [
            'data' => $data,
            'message' => $message,
            'success' => $success
        ]);
    }

    public function delete_chord($request, $response, $args)
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

        $file = $this->_settings['basePath'] . '/../uploads/chords/'. $args['id'];
        if (!file_exists($file)) {
            return false;
        } else {
            unlink($file);
            echo true;
        }
    }

    public function artists($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        $datas = [];
        foreach(glob( $this->_settings['basePath'] . '/../uploads/songs/*.webp') as $artist) {
            if (file_exists($artist)) {
                $pathinfo = pathinfo($artist);
                $pathinfo['folder_name'] = 'uploads/songs/'. $pathinfo['basename'];
                if (!is_numeric($pathinfo['filename'])) {
                    $pathinfo['artist_name'] = $pathinfo['filename'];
                    if (strpos($pathinfo['filename'], "-") !== false) {
                        $pathinfo['artist_name'] = str_replace("-", " ", $pathinfo['artist_name']);
                    }
                    $pathinfo['last_modified'] = date("Y-m-d H:i:s.", filemtime($artist));
                    $datas[] = $pathinfo;
                }
            }
        }

        return $this->_container->module->render($response, 'songs/image_artists.html', [
            'datas' => $datas
        ]);
    }

    public function upload_artist($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        $message = null; $success = false;
        if (isset($_POST['Artists']) && isset($_FILES['Artists'])){
            if (isset($_FILES['Artists']) && !empty($_FILES['Artists']['name']['image'])) {
                $path_info = pathinfo($_FILES['Artists']['name']['image']);
                if (in_array($path_info['extension'], ['webp'])) {
                    $upload_folder = 'uploads/songs';
                    $file_name = $path_info['basename'];
                    $artist_slug = \ExtensionsModel\PostModel::createSlug($_POST['Artists']['title']);
                    if ($path_info['filename'] != $artist_slug) {
                        $file_name = $artist_slug.'.'.$path_info['extension'];
                    }
                    $uploadfile = $upload_folder . '/' . $file_name;
                    try {
                        $upload = move_uploaded_file($_FILES['Artists']['tmp_name']['image'], $uploadfile);
                        if ($upload) {
                            $message = $file_name.' berhasil diupload';
                            $success = true;
                        } else {
                            $message = $file_name.' Gagal diupload.';
                            $success = false;
                        }
                    } catch (\Exception $e) {}
                } else {
                    $message = "Hanya file .webp yang diperbolehkan";
                }
            }

            return $response->withJson([
                'message' => $message,
                'status' => ($success)? 'success' : 'failed'
            ]);
        }

        return $this->_container->module->render($response, 'songs/image_upload_artists.html', [
            'message' => ($message) ? $message : null,
            'success' => $success
        ]);
    }

    public function update_artist($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        if (empty($args['id']))
            return false;

        $file = $this->_settings['basePath'] . '/../uploads/songs/'. $args['id'];
        if (!file_exists($file)) {
            return false;
        }
        $data = pathinfo($file);
        $data['folder_name'] = 'uploads/songs/'. $data['basename'];
        $data['artist_name'] = $data['filename'];
        if (strpos($data['filename'], "-") !== false) {
            $data['artist_name'] = ucwords(str_replace("-", " ", $data['artist_name']));
        }

        $message = null; $success = false;
        if (isset($_POST['Artists']) && isset($_FILES['Artists'])){
            if (isset($_FILES['Artists']) && !empty($_FILES['Artists']['name']['image'])) {
                $path_info = pathinfo($_FILES['Artists']['name']['image']);
                if (in_array($path_info['extension'], ['webp'])) {
                    $upload_folder = 'uploads/songs';
                    $file_name = $path_info['basename'];
                    $artist_slug = \ExtensionsModel\PostModel::createSlug($_POST['Artists']['title']);
                    if ($path_info['filename'] != $artist_slug) {
                        $file_name = $artist_slug.'.'.$path_info['extension'];
                    }
                    $uploadfile = $upload_folder . '/' . $file_name;
                    try {
                        $upload = move_uploaded_file($_FILES['Artists']['tmp_name']['image'], $uploadfile);
                        if ($upload) {
                            unlink($file);
                            $message = $file_name.' berhasil diupload';
                            $success = true;
                        } else {
                            $message = $file_name.' Gagal diupload.';
                            $success = false;
                        }
                    } catch (\Exception $e) {}
                } else {
                    $message = "Hanya file .webp yang diperbolehkan";
                }
            }

            return $response->withJson([
                'message' => $message,
                'status' => ($success)? 'success' : 'failed'
            ]);
        }

        return $this->_container->module->render($response, 'songs/image_update_artist.html', [
            'data' => $data,
            'message' => $message,
            'success' => $success
        ]);
    }

    public function delete_artist($request, $response, $args)
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

        $file = $this->_settings['basePath'] . '/../uploads/songs/'. $args['id'];
        if (!file_exists($file)) {
            return false;
        } else {
            unlink($file);
            echo true;
        }
    }
}