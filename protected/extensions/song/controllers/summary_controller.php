<?php

namespace Extensions\Controllers;

use Components\BaseController as BaseController;

class SummaryController extends BaseController
{
    public function __construct($app, $user)
    {
        parent::__construct($app, $user);
    }

    public function register($app)
    {
        $app->map(['GET'], '/dashboard', [$this, 'dashboard']);
    }

    public function accessRules()
    {
        return [
            ['allow',
                'actions' => ['dashboard'],
                'users' => ['@'],
            ],
            ['deny',
                'users' => ['*'],
            ],
        ];
    }

    public function dashboard($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if (!$isAllowed) {
            return $this->notAllowedAction();
        }

        $model = new \ExtensionsModel\SongModel();

        return $this->_container->module->render($response, 'songs/summary_dashboard.html', [
            'model' => $model
        ]);
    }
}