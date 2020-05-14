<?php

namespace Mobile\Controllers;

use Components\ApiBaseController as BaseController;

class ArtistController extends BaseController
{
    public function __construct($app, $user)
    {
        parent::__construct($app, $user);
    }

    public function register($app)
    {
        $app->map(['GET'], '/list', [$this, 'get_list']);
        $app->map(['GET'], '/list-songs', [$this, 'get_list_songs']);
    }

    public function accessRules()
    {
        return [
            ['allow',
                'actions' => ['list', 'list-songs'],
                'users'=> ['@'],
            ]
        ];
    }

    public function get_list($request, $response, $args)
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
		if (file_exists($this->_settings['basePath'] .'/data/artists.json')) {
			$content = file_get_contents($this->_settings['basePath'] .'/data/artists.json');
			if (empty($content)) {
				$folder = $this->_settings['basePath'] .'/data/songs';
				foreach (glob($folder .'/*.json') as $songs) {
					$cname = basename($songs, '.json');
					if (!empty($cname)) {
						$exps = explode("_", $cname);
						if (!empty($exps[0]) && !array_key_exists($exps[0], $items)) {
							$name = ucfirst($exps[0]);
							if (strpos($exps[0], '-') !== false) {
								$name = ucwords(str_replace("-"," ", $exps[0]));
							}
							$items[$exps[0]] = $name;
						}
					}
				}
				ksort($items);
				if (count($items) > 0) {
					try {
						file_put_contents($this->_settings['basePath'] .'/data/artists.json', json_encode($items));
					} catch (\Exception $e){}
				}
			} else {
				$items = json_decode($content, true);
			}
		}

		

        if (is_array($items)){
            $result['success'] = 1;
            $result['data'] = $items;
        } else {
            $result = [
                'success' => 0,
                'message' => "Data artist tidak ditemukan.",
            ];
        }

        return $response->withJson($result, 201);
    }

	public function get_list_songs($request, $response, $args)
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

		$items = [];
		if (array_key_exists('artist_slug', $params) &&  !empty($params['artist_slug'])) {
			$dir = $this->_settings['basePath'] .'/data/songs';
			foreach (glob($dir . '/'. $params['artist_slug'] .'_*') as $item) {
				if (file_exists($item)) {
					$content = file_get_contents($item);
					if (!empty($content)) {
						$json = json_decode($content, true);
						if (is_array($json)) {
							array_push($items, $json);
						}
					}
				}
			}
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
