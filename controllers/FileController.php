<?php

namespace app\controllers;

use yii\web\UploadedFile;
use yii;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use app\models\Files;
use app\models\Users;
use app\models\Coauthors;
use app\models\Authors;


use Codeception\Lib\Interfaces\ActiveRecord;

use FFI;
use PharIo\Manifest\Author;

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
                    $model->url = Yii::$app->request->getHostInfo() . '/api/files/' . $model->file_id;


                    if (!$model->isUniqueName($identity->id, $model->name)) {
                        $i = 1;
                        $name = $model->name;
                        while (!$model->isUniqueName($identity->id, $name)) {
                            $name = $model->name . "($i)" . $model->extension;
                            $i++;
                        }
                        // var_dump('rer');die;
                        $model->name = $name;
                    }
                    if ($model->save()) {
                        $model = Files::findOne(['file_id' => $model->file_id]);
                        $fileInfo = new Authors();
                        $fileInfo->user_id = $identity->id;
                        $fileInfo->file_id = $model->id;
                        $fileInfo->role_id = 1;
                        $fileInfo->save(false);

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
            if ($file) {
                $user = Authors::findOne(['user_id' => $identity->id, 'file_id' => $file->id]);
                if ($user) {

                    $file->name = $data['name'];
                    $file->validate();
                    if (!$file->hasErrors()) {

                        if (!$file->isUniqueName($identity->id, $file->name)) {
                            $i = 1;
                            $name = $file->name;
                            while (!$file->isUniqueName($identity->id, $name)) {
                                $name = $file->name . "($i)" . $file->extension;
                                $i++;
                            }
                            // var_dump('rer');die;
                            $file->name = $name;
                        }
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
            if ($file) {
                $user = Authors::findOne(['user_id' => $identity->id, 'file_id' => $file->id]);
                if ($user) {
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
            $auth = Authors::findOne(['user_id' => $identity->id, 'file_id' => $file->id]);
            if ($file) {
                if ($auth && Authors::isAuthor($auth->role_id)) {
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
                $auth = Authors::findOne(['user_id' => $identity->id, 'file_id' => $file->id]);
                if ($auth && Authors::isAuthor($auth->role_id)) {
                    $user = Users::findOne(['email' => $data['email']]);
                    if ($user) {
                        $co_author = new Authors;
                        $co_author->file_id = $file->id;
                        $co_author->user_id = $user->id;
                        $co_author->role_id = 2;
                        if ($co_author->save()) {
                            // var_dump('fds');die;
                            $coauths = Authors::find()
                                ->select([
                                    'first_name',
                                    'last_name',
                                    'email',
                                    'role.role'
                                ])
                                ->innerJoin('file', 'file.id = coauthors.file_id')
                                ->innerJoin('user', 'user.id = coauthors.user_id')
                                ->innerJoin('role', 'Coauthors.role_id = role.id')
                                ->where(['file.file_id' => $file->file_id])
                                ->asArray()
                                ->all();
                            // var_dump($coauths);die;
                            if (!empty($coauths)) {
                                foreach ($coauths as $auth) {
                                    $result[$file['file_id']]['accesses'][] = [
                                        'fullname' => $auth['last_name'] . $auth['last_name'],
                                        'email' => $auth['email'],
                                        'type' => $auth['role'],
                                    ];
                                }
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

                $auth = Authors::findOne(['user_id' => $identity->id, 'file_id' => $file->id]);
                if ($auth && Authors::isAuthor($auth->role_id)) {
                    $user = Users::findOne(['email' => $data['email']]);
                    if ($user) {

                        $co_author = Authors::findOne(['user_id' => $user->id, 'file_id' => $file->id, 'role_id' => 2]);
                        if ($co_author && $co_author->delete()) {

                            // var_dump($file->co_author);die;

                            // var_dump('fds');die;
                            $coauths = Authors::find()
                                ->select([
                                    'first_name',
                                    'last_name',
                                    'email',
                                    'role.role'
                                ])
                                ->innerJoin('file', 'file.id = coauthors.file_id')
                                ->innerJoin('user', 'user.id = coauthors.user_id')
                                ->where(['file.file_id' => $file->file_id])

                                ->innerJoin('role', 'coauthors.role_id = role.id')
                                ->asArray()
                                ->all();
                            // var_dump($coauths);die;
                            if (!empty($coauths)) {
                                foreach ($coauths as $auth) {
                                    $result[$file['file_id']]['accesses'][] = [
                                        'fullname' => $auth['last_name'] . $auth['last_name'],
                                        'email' => $auth['email'],
                                        'type' => $auth['role'],
                                    ];
                                }
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
                ])

                ->asArray()
                ->all();
            foreach ($files as $file) {
                $result[$file['file_id']] = [
                    'file_id' => $file['file_id'],
                    'name' => $file['name'],
                    'code' => 200,
                    'url' => $file['url'],
                    'accesses' => []
                ];

                // Добавляем автора

                $coauths = Authors::find()
                    ->select([
                        'first_name',
                        'last_name',
                        'email',
                        'role.role'
                    ])
                    ->innerJoin('file', 'file.id = coauthors.file_id')
                    ->where(['file.file_id' => $file['file_id']])
                    ->innerJoin('user', 'user.id = coauthors.user_id')
                    ->innerJoin('role', 'coauthors.role_id = role.id')
                    ->asArray()
                    ->all();
                // var_dump($coauths);
                // die;
                if (!empty($coauths)) {
                    foreach ($coauths as $auth) {
                        $result[$file['file_id']]['accesses'][] = [
                            'fullname' => $auth['last_name'] . $auth['last_name'],
                            'email' => $auth['email'],
                            'type' => $auth['role'],
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
