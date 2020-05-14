<?php

namespace Extensions\Controllers;

use Components\BaseController as BaseController;

class FausersController extends BaseController
{
    public function __construct($app, $user)
    {
        parent::__construct($app, $user);
    }

    public function register($app)
    {
        $app->map(['GET'], '/view', [$this, 'view']);
        $app->map(['GET'], '/detail/[{id}]', [$this, 'detail']);
        $app->map(['POST'], '/delete/[{id}]', [$this, 'delete']);
    }

    public function accessRules()
    {
        return [
            ['allow',
                'actions' => ['view', 'detail', 'delete'],
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

        $model = new \ExtensionsModel\TwofaModel();
        $datas = $model->getItems();

        return $this->_container->module->render($response, 'twofas/view.html', [
            'datas' => $datas
        ]);
    }

    public function detail($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        if (empty($args['id']))
            return false;

        $model = new \ExtensionsModel\TwofaModel();
        $item = $model->getItem($args['id']);

        $ga = new \ExtensionsComponents\PHPGangsta_GoogleAuthenticator();
        $_model = \ExtensionsModel\TwofaModel::model()->findByAttributes(['admin_id' => $args['id']]);
        $secret = $ga->createSecret(16);
        if (!$_model instanceof \RedBeanPHP\OODBBean) {
            $model->admin_id = $args['id'];
            $model->secret_code = $secret;
            $model->created_at = date('c');
            $save = \ExtensionsModel\TwofaModel::model()->save($model);
        } else {
            if (empty($_model->secret_code)) {
                $_model->secret_code = $secret;
                $update = \ExtensionsModel\TwofaModel::model()->update($_model);
            } else {
                $secret = $_model->secret_code;
            }
        }

        $QRCodeGoogleUrl = $ga->getQRCodeGoogleUrl($item['email'], $secret, $this->_settings['params']['site_name']);

        return $this->_container->module->render($response, 'twofas/detail.html', [
            'model' => $model,
            'item' => $item,
            'QRCodeGoogleUrl' => $QRCodeGoogleUrl
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

        $model = \ExtensionsModel\TwofaModel::model()->findByPk($args['id']);
        $delete = \ExtensionsModel\TwofaModel::model()->delete($model);
        if ($delete) {
            echo true;
        }
    }
}