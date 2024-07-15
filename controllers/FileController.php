<?php

namespace app\controllers;

use yii\web\UploadedFile;
use yii;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use app\models\Files;
use app\models\Users;
use app\models\Coauthors;

use Codeception\Lib\Interfaces\ActiveRecord;

use FFI;

class FileController extends \yii\rest\ActiveController
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
            'only'  => ['edd-file', 'edit-file', 'delete-file', 'get-file', 'add-co', 'del-co', 'get-all'],
            'optional'  => ['edd-file', 'edit-file', 'delete-file', 'get-file', 'add-co', 'del-co', 'get-all'],


        ];
        $behaviors['authenticator'] = $auth;

        return $behaviors;
    }


    public function actions()
    {
        $actions = parent::actions();
        unset($actions['delete'], $actions['create'], $actions['update'], $actions['view'], $actions['index']);
        // var_dump('das');die;
        return $actions;
    }


    public function actionEddFile()
    {
        $identity = Yii::$app->user->identity;
        if ($identity) {
            $result = [];
            $files = UploadedFile::getInstancesByName('files');
            foreach ($files as $file) {
                $model = new Files();
                $model->file = $file;

                if ($res = $model->validate()) {

                    $model->name  = $model->file->baseName;
                    $model->extension  = $model->file->extension;
                    $model->file_id = Yii::$app->security->generateRandomString(10);
                    while (!$model->validate()) {
                        $model->file_id = Yii::$app->security->generateRandomString(10);
                    }
                    $model->user_id = $identity->id;

                    $model->url = Yii::$app->request->getHostInfo() . '/api/files/' . $model->file_id;

                    if ($model->save()) {
                        $dir = Yii::getAlias('@app/uploads/');

                        if (!file_exists($dir)) {
                            mkdir($dir, 0777, true);
                        }

                        $pathInfo = $dir . $model->file_id . '.' . $model->extension;

                        if ($file->saveAs($pathInfo)) {
                            Yii::$app->response->statusCode = 200;

                            $result[] = [
                                [
                                    "success" => true,
                                    "code" => 200,
                                    "message" => "Success",
                                    "name" => $model->name,
                                    "url" => $model->url,
                                    "file_id" => $model->file_id,
                                ],
                            ];
                        } else {
                            Yii::$app->response->statusCode = 200;

                            $result[] = [
                                "success" => false,
                                "message" => $model->errors,
                                "name" => $model->name
                            ];
                        }
                    }
                } else {
                    Yii::$app->response->statusCode = 422;
                    $result[] = [
                        'code' => 422,
                        'success' => false,
                        "errors" => $model->errors,
                    ];
                }
            }
        } else {
            Yii::$app->response->statusCode = 403;
            $result = [
                'success' => false,
                'message' => 'Forbidden for you'
            ];
        }
        return $this->asJson($result);
    }

    public function actionEditFile($file_id = null)
    {
        $identity = Yii::$app->user->identity;
        $result = [];
        if ($identity) {
            $data = Yii::$app->request->post();
            $file = Files::findOne(['file_id' => $file_id]);
            // var_dump($file);die;
            if ($file) {
                if ($identity->id == $file->user_id || $identity->id == $file->co_author) {
                    // var_dump();die;

                    $file->name = $data['name'];
                    $file->validate();
                    if (!$file->hasErrors()) {

                        $file->save();
                        Yii::$app->response->statusCode = 200;

                        $result[] = [
                            "success" => true,
                            "code" => 200,
                            "message" => "Renamed"
                        ];
                    } else {

                        Yii::$app->response->statusCode = 422;
                        $result[] = [
                            'code' => 422,
                            // 'code' 
                            'success' => false,
                            "errors" => $file->errors,
                        ];
                    }
                } else {
                    Yii::$app->response->statusCode = 403;
                    $result = [
                        'success' => false,
                        'message' => 'Forbidden for you'
                    ];
                }
            } else {
                Yii::$app->response->statusCode = 404;
                $result[] = [
                    [
                        "message" => "Not found",
                        "code" => 404
                    ]

                ];
            }
        } else {
            Yii::$app->response->statusCode = 403;
            $result = [
                'success' => false,
                'message' => 'Forbidden for you'
            ];
        }
        return $this->asJson($result);
    }


    public function actionDeleteFile($file_id = null)
    {
        $identity = Yii::$app->user->identity;
        $result = [];
        if ($identity) {
            $file = Files::findOne(['file_id' => $file_id]);
            // var_dump($file);die;
            if ($file) {
                if ($identity->id == $file->user_id || $identity->id == $file->co_author) {
                    $dir = Yii::getAlias('@app/uploads/');
                    $pathInfo = $dir . $file->file_id . '.' . $file->extension;
                    if (file_exists($pathInfo)) {
                        // var_dump($pathInfo);die;
                        $file->delete();
                        unlink($pathInfo);
                        $result[] = [
                            "success" => true,
                            "code" => 200,
                            "message" => "file deleted"
                        ];
                    } else {
                        Yii::$app->response->statusCode = 404;
                        $result[] = [
                            [
                                "message" => "Not found",
                                "code" => 404
                            ]

                        ];
                    }
                } else {
                    Yii::$app->response->statusCode = 403;
                    $result = [
                        'success' => false,
                        'message' => 'Forbidden for you'
                    ];
                }
            } else {
                Yii::$app->response->statusCode = 404;
                $result[] = [
                    [
                        "message" => "Not found",
                        "code" => 404
                    ]

                ];
            }
        } else {
            Yii::$app->response->statusCode = 403;
            $result = [
                'success' => false,
                'message' => 'Forbidden for you'
            ];
        }
        return $this->asJson($result);
    }


    public function actionGetFile($file_id = null)
    {
        $identity = Yii::$app->user->identity;
        $result = [];
        // var_dump('dasd');die;
        if ($identity) {
            $file = Files::findOne(['file_id' => $file_id]);
            // var_dump($file);die;
            if ($file) {
                if ($identity->id == $file->user_id || $identity->id == $file->co_author) {
                    $dir = Yii::getAlias('@app/uploads/');
                    $pathInfo = $dir . $file->file_id . '.' . $file->extension;
                    if (file_exists($pathInfo)) {
                        Yii::$app->response->statusCode = 200;
                        Yii::$app->response->sendFile($pathInfo)->send();
                    } else {
                        Yii::$app->response->statusCode = 404;
                        $result[] = [
                            [
                                "message" => "Not found",
                                "code" => 404
                            ]

                        ];
                    }
                } else {
                    Yii::$app->response->statusCode = 403;
                    $result = [
                        'success' => false,
                        'message' => 'Forbidden for you'
                    ];
                }
            } else {
                Yii::$app->response->statusCode = 404;
                $result[] = [
                    [
                        "message" => "Not found",
                        "code" => 404
                    ]

                ];
            }
        } else {
            Yii::$app->response->statusCode = 403;
            $result = [
                'success' => false,
                'message' => 'Forbidden for you'
            ];
        }
        return $this->asJson($result);
    }

    public function actionAddCo($file_id = null)
    {
        $data = Yii::$app->request->post();
        $identity = Yii::$app->user->identity;
        $result = [];
        // var_dump('asdad');die;
        if ($identity) {
            $file = Files::findOne(['file_id' => $file_id]);
            if ($file) {
                $file->scenario = 'coAuth';
                if ($identity->id == $file->user_id) {
                    $user = Users::findOne(['email' => $data['email']]);
                    if ($user) {

                        $co_author = new CoAuthors;
                        $co_author->file_id = $file->id;
                        $co_author->co_auth_id = $user->id;
                        if ($co_author->save()) {

                            $file->co_author = $user->id;
                            // var_dump($file->co_author);die;
                            $author = Files::find()
                                ->select([
                                    'first_name',
                                    'last_name',
                                    'email',
                                ])
                                ->innerJoin('user as us1', 'us1.id = file.user_id')
                                ->asArray()
                                ->all();

                            // var_dump('fds');die;
                            $users = Coauthors::find()
                                ->select([
                                    'first_name',
                                    'last_name',
                                    'email',
                                ])
                                ->innerJoin('user', 'user.id = coauthors.co_auth_id')
                                ->innerJoin('file', 'file.id = coauthors.file_id')
                                ->asArray()
                                ->all();
                            Yii::$app->response->statusCode = 200;
                            $result[] = [
                                'name' => $author[0]['first_name'] . $author[0]['last_name'],
                                'email' => $author[0]['email'],
                                'type' => 'author,'

                            ];
                            foreach ($users as $user) {

                                $result[] = [
                                    'name' => $user['first_name'] . $user['last_name'],
                                    'email' => $user['email'],
                                    'type' => 'co_auth',
                                ];
                            }
                        } else {
                            Yii::$app->response->statusCode = 422;
                            $result[] = [
                                'code' => 422,
                                // 'code' 
                                'success' => false,
                                "errors" => $co_author->errors,
                            ];
                        }
                    } else {
                        Yii::$app->response->statusCode = 404;
                        $result[] = [
                            [
                                "message" => "Not found",
                                "code" => 404
                            ]

                        ];
                    }
                } else {
                    Yii::$app->response->statusCode = 403;
                    $result = [
                        'success' => false,
                        'message' => 'Forbidden for you'
                    ];
                }
            } else {
                Yii::$app->response->statusCode = 404;
                $result[] = [
                    [
                        "message" => "Not found",
                        "code" => 404
                    ]

                ];
            }
        } else {
            Yii::$app->response->statusCode = 403;
            $result = [
                'success' => false,
                'message' => 'Forbidden for you'
            ];
        }
        return $this->asJson($result);
    }


    public function actionDelCo($file_id = null)
    {
        $data = Yii::$app->request->post();
        $identity = Yii::$app->user->identity;
        $result = [];
        // var_dump('asdad');die;
        if ($identity) {
            $file = Files::findOne(['file_id' => $file_id]);
            if ($file) {
                $file->scenario = 'coAuth';
                if ($identity->id == $file->user_id) {
                    $user = Users::findOne(['email' => $data['email']]);
                    if ($user) {

                        $co_author = Coauthors::findOne(['co_auth_id' => $user->id]);
                        if ($co_author->delete()) {

                            $file->co_author = $user->id;
                            // var_dump($file->co_author);die;
                            $author = Files::find()
                                ->select([
                                    'first_name',
                                    'last_name',
                                    'email',
                                ])
                                ->innerJoin('user as us1', 'us1.id = file.user_id')
                                ->asArray()
                                ->all();

                            // var_dump('fds');die;
                            $users = Coauthors::find()
                                ->select([
                                    'first_name',
                                    'last_name',
                                    'email',
                                ])
                                ->innerJoin('user', 'user.id = coauthors.co_auth_id')
                                ->innerJoin('file', 'file.id = coauthors.file_id')
                                ->asArray()
                                ->all();
                            Yii::$app->response->statusCode = 200;
                            $result[] = [
                                'name' => $author[0]['first_name'] . $author[0]['last_name'],
                                'email' => $author[0]['email'],
                                'type' => 'author,'

                            ];
                            foreach ($users as $user) {

                                $result[] = [
                                    'name' => $user['first_name'] . $user['last_name'],
                                    'email' => $user['email'],
                                    'type' => 'co_auth',
                                ];
                            }
                        } else {
                            Yii::$app->response->statusCode = 404;
                            $result[] = [
                                'code' => 404,
                                'success' => false,
                                'message' => 'user not found',
                            ];
                        }
                    } else {
                        Yii::$app->response->statusCode = 404;
                        $result[] = [
                            [
                                "message" => "Not found",
                                "code" => 404
                            ]

                        ];
                    }
                } else {
                    Yii::$app->response->statusCode = 403;
                    $result = [
                        'success' => false,
                        'message' => 'Forbidden for you'
                    ];
                }
            } else {
                Yii::$app->response->statusCode = 404;
                $result[] = [
                    [
                        "message" => "Not found",
                        "code" => 404
                    ]

                ];
            }
        } else {
            Yii::$app->response->statusCode = 403;
            $result = [
                'success' => false,
                'message' => 'Forbidden for you'
            ];
        }
        return $this->asJson($result);
    }

    public function actionGetAll()
    {
        // var_dump('fsdfdf');die;
        $identity = Yii::$app->user->identity;
        $result = [];
        if ($identity) {
            // var_dump($file);die;
            $files  = Files::find()
                ->select([
                    'file.file_id',
                    'url',
                    'file.name',
                    'auth.first_name as authName',
                    'auth.last_name as authNname',
                    'auth.email as authEmail',
                ])
                ->innerJoin('user as auth', 'auth.id = file.user_id')
                ->asArray()
                ->all();

                // var_dump($files);die;
            foreach ($files as $file) {
                    $result[$file['file_id']] = [
                        'file_id' => $file['file_id'],
                        'name' => $file['name'],
                        'code' => 200,
                        'url' => $file['url'],
                        'accesses' => []
                    ];

                    // Добавляем автора
                    $result[$file['file_id']]['accesses'][] = [
                        'fullname' => $file['authName'].$file['authNname'],
                        'email' => $file['authEmail'],
                        'type' => 'author'
                    ];

                    $coauths = Coauthors::find()
                    ->select([
                        'first_name',
                        'last_name',
                        'email',
                    ])
                    ->innerJoin('file', 'file.id = Coauthors.file_id')
                    ->innerJoin('user', 'user.id = Coauthors.co_auth_id')
                    ->asArray()
                    ->all();
                    if (!empty($coauths)) {
                        foreach ($coauths as $auth) {
                            $result[$file['file_id']]['accesses'][] = [
                                'fullname' => $auth['last_name'].$auth['last_name'],
                                'email' => $auth['email'],
                                'type' => 'co_author'
                            ];
                        }
                    }
                    
              
            }
        } else {
            Yii::$app->response->statusCode = 403;
            $result = [
                'success' => false,
                'message' => 'Forbidden for you'
            ];
        }
        return $this->asJson($result);
    }
}
