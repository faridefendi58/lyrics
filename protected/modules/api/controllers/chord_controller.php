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
}
