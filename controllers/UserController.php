<?php

namespace app\controllers;

use yii;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use app\models\Users;

class UserController extends \yii\rest\ActiveController
{
    // public function actionIndex()
    // {
    //     return $this->render('index');
    // }

    public $enableCrsfValidation = false;
    public $modelClass = '';

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => [(isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'http://' . $_SERVER['REMOTE_ADDR'])],
                'Access-Control-Request-Method' => ['GET', 'POST', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['content-type', 'Authorization'],

            ],
            'actions' => [
                'logout' => [
                    'Access-Control-Allow-Credentials' => true,
                ]
            ]
        ];

        $auth = [
            'class' => HttpBearerAuth::class,
            'only'  => ['logout'],
            'optional'  => ['logout'],


        ];
        $behaviors['authenticator'] = $auth;

        return $behaviors;
        // var_dump('dsad');die;
    }


    public function actions()
    {
        $actions = parent::actions();
        unset($actions['delete'], $actions['create'], $actions['update'], $actions['view'], $actions['index']);
        // var_dump('das');die;
        return $actions;
    }

    public function actionRegister()
    {
        // var_dump('dsad');die;
        $data = Yii::$app->request->post();
        $model = new Users();
        $model->scenario = Users::SCENARIO_REGISTER;
        $model->load($data, '');
        $model->validate();
        if (!$model->hasErrors()) {
            $model->token = Yii::$app->security->generateRandomString();
            while (!$model->validate()) {
                $model->token = Yii::$app->security->generateRandomString();
            }
            $model->password = Yii::$app->security->generatePasswordHash($model->password);
            $model->save(false);
            Yii::$app->response->statusCode = 201;
            $answer = [
                'code' => 201,
                'success' => true,
                "message" => "Success",
                "token" => $model->token,

            ];
        } else {
            Yii::$app->response->statusCode = 422;
            $answer = [
                'code' => 422,
                // 'code' 
                'success' => false,
                "errors" => $model->errors,
                // "token" => $model->token,
            ];
        }
        return $this->asJson($answer);
    }

    public function actionLogin()
    {
        $data = Yii::$app->request->post();
        $model = new Users;
        $model->load($data, '');
        $model->validate();
        // var_dump('dasd');die;
        if (!$model->hasErrors()) {
            $user = Users::findone(['email' => $model->email]);

            if (!empty($user) && $user->validatePassword($model->password)) {
                $model = $user;
                $model->token = Yii::$app->security->generateRandomString();
                while (!$model->save()) {
                    $model->token = Yii::$app->security->generateRandomString();
                }

                if ($model->token) {
                    Yii::$app->response->statusCode = 200;
                    $answer = [
                        'code' => 200,
                        'success' => true,
                        "message" => "Success",
                        "token" => $model->token,

                    ];
                }
            } else {
                Yii::$app->response->statusCode = 401;
                $answer = [
                    // 'code' => 401,
                    'success' => false,
                    "message" => "Authentication failed",
                    // 'errors' => $model->errors,

                ];
            }
        } else {
            Yii::$app->response->statusCode = 422;
            $answer = [
                'code' => 422,
                // 'code' 
                'success' => false,
                "errors" => $model->errors,
                // "token" => $model->token,
            ];
        }

        return $this->asJson($answer);
    }


    // public function beforeAction()
    // {
    // }
    public function actionLogout()
    {
        $identity = Yii::$app->user->identity;
        if ($identity) {
            $user = Users::findOne($identity->id);
            $user->token = null;
            $user->save();
            Yii::$app->response->statusCode = 204;
            Yii::$app->response->send();
        } else {
            Yii::$app->response->statusCode = 403;
            return $this->asJson([
                'success' => false,
                'message' => 'Forbidden for you'
            ]);
        }
    }
}
