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
        $params['limit'] = 100;
        $params['type'] = 'chord';
        $model = new \ExtensionsModel\SongModel();
        $items = $model->getSearch($params);
        $result = $items;

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