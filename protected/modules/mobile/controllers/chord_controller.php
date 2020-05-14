<?php

namespace Mobile\Controllers;

use Components\ApiBaseController as BaseController;

class ChordController extends BaseController
{
    public function __construct($app, $user)
    {
        parent::__construct($app, $user);
    }

    public function register($app)
    {
        $app->map(['GET'], '/search', [$this, 'get_search']);
        $app->map(['POST'], '/request', [$this, 'get_request']);
        $app->map(['GET'], '[/{artist}[/{slug}]]', [$this, 'get_song']);
    }

    public function accessRules()
    {
        return [
            ['allow',
                'actions' => ['search'],
                'users'=> ['@'],
            ]
        ];
    }

    public function get_search($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);

        if (!$isAllowed['allow']) {
            $result = [
                'success' => 0,
                'message' => $isAllowed['message'],
            ];
            return $response->withJson($result, 201);
        }

        $result = [];
        $params = $request->getParams();
        if (!array_key_exists('limit', $params)) {
            $params['limit'] = 100;
        }

		$items = [];
		if (array_key_exists('cached', $params) &&  $params['cached'] > 0) {
			$file = $this->_settings['basePath'] .'/data/chord_latest.json';
            if(file_exists($file)) {
                $content = file_get_contents($file);
				$items = json_decode($content, true);
            }
        } else {
		    $params['type'] = 'chord';
		    $model = new \ExtensionsModel\SongModel();
		    $items = $model->getSearch($params);
		}
        if (is_array($items)){
            $result['success'] = 1;
            $result['data'] = $items;
        } else {
            $result = [
                'success' => 0,
                'message' => "Data chord tidak ditemukan.",
            ];
        }

        return $response->withJson($result, 201);
    }

    public function get_song($request, $response, $args)
    {
		$params = $request->getParams();
		$api_data = 'protected/data/api_keys.json';
		if(file_exists($api_data)) {
            $apies = file_get_contents($api_data);
            if (!empty($apies)) {
                $apies_dt = json_decode($apies, true);
				if (is_array($apies_dt) && !in_array($params['api-key'], $apies_dt)) {
					return;
				}
            }
        }

		$hide_adds = false;
		if (array_key_exists('hide_adds', $params) && $params['hide_adds'] > 0) {
			$hide_adds = true;
		}

        $model = new \ExtensionsModel\SongModel();

        $dir = 'protected/data/songs/';
        $file = $dir. $args['artist'].'_'.$args['slug'].'.json';
        $data = [];
        if(file_exists($file)) {
            $data = file_get_contents($file);
            if (!empty($data)) {
                $data = json_decode($data, true);
            }
        }

        return $this->_container->view->render($response, 'song_chord_mobile.phtml', [
            'data' => $data,
            'msong' => $model,
			'hide_adds' => $hide_adds,
			'api_key' => $params['api-key']
        ]);
    }

	public function get_request($request, $response, $args)
    {
		$result = ['success' => 0];

		$params = $request->getParams();
		$api_data = 'protected/data/api_keys.json';
		if(file_exists($api_data)) {
            $apies = file_get_contents($api_data);
            if (!empty($apies)) {
                $apies_dt = json_decode($apies, true);
				if (is_array($apies_dt) && !in_array($params['api-key'], $apies_dt)) {
					$result['message'] = 'Illegal request!';
					return $response->withJson($result, 201);
				}
            }
        }

		if (isset($_POST['name']) && isset($_POST['title']) && isset($_POST['artist'])){
		    if (empty($_POST['name'])
		        && empty($_POST['email'])
		        && empty($_POST['title'])
		        && empty($_POST['artist'])) {
		        $result['message'] = "Nama, email, judul lagu, dan nama penyanyi tidak boleh dikosongi";
		    }

		    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
		        $result['message'] = $_POST['email']." bukan alamat email yang valid";
		    }

			$success = true;
		    try {
		        // save to db
		        $model = new \ExtensionsModel\SongRequestModel('create');
		        $model->name = $_POST['name'];
		        $model->email = $_POST['email'];
		        $model->song_title = $_POST['title'];
		        $model->song_artist = $_POST['artist'];
		        if (isset($_POST['type'])) {
		            $model->type = $_POST['type'];
		        }
		        if (isset($_POST['notes'])) {
		            $model->notes = $_POST['notes'];
		        }
		        $model->created_at = date("Y-m-d H:i:s");
		        $save = \ExtensionsModel\SongRequestModel::model()->save(@$model);
				if ($save) {
					$success = true;
				}
		    } catch (Exception $e) {
				$result['success'] = 1;
		        $result['message'] = 'Unable to save your request';
		    }

			if ($success) {
				$result['success'] = 1;
				$result['message'] = 'Request Anda berhasil dikirim. Kami akan segera menindaklanjuti permintaan Anda.';
			}
		}

		return $response->withJson($result);
	}
}
