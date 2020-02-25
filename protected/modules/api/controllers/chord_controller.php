<?php

namespace Api\Controllers;

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
            'msong' => $model
        ]);
    }
}
