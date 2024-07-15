<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'asd',
            'baseUrl' => '',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
                'multipart/form-data' => 'yii\web\MultipartFormDataParser'
            ]

        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\Users',
            'enableAutoLogin' => true,
            'enableSession' => false,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        /*
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],
        ],

        */
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                // ['class' => 'yii\rest\UrlRule', 'controller' => 'user'],
                'OPTIONS <prefix:.*>/register' => 'user/options',
                'POST <prefix:.*>/register' => 'user/register',

                'OPTIONS <prefix:.*>/login' => 'user/options',
                'POST <prefix:.*>/login' => 'user/login',

                'OPTIONS <prefix:.*>/logout' => 'user/options',
                'GET <prefix:.*>/logout' => 'user/logout',
                [
                    'pluralize' => true,
                    'prefix' => '<prefix:.*>',
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'file',
                    'extraPatterns' => [
                        'OPTIONS /' => 'edd-file',
                        'POST /' => 'edd-file',
                        
                        'OPTIONS <file_id>' => 'edit-file',
                        'PATCH <file_id>' => 'edit-file',

                        'DELETE <file_id>' => 'delete-file',

                        'OPTIONS disk' => 'get-all',
                        'GET disk' => 'get-all',
                        'GET <file_id>' => 'get-file',

                        'OPTIONS <file_id>/access' => 'add-co',

                        'POST <file_id>/access' => 'add-co',

                        'DELETE <file_id>/access' => 'del-co',

                        

                    ]

                    ],

            ],
        ],

        'response' => [
            // ...
            'format' =>  \yii\web\Response::FORMAT_JSON,
            'charset' => 'UTF-8',
            'formatters' => [
                \yii\web\Response::FORMAT_JSON => [
                    'class' => 'yii\web\JsonResponseFormatter',
                    'prettyPrint' => YII_DEBUG, // use "pretty" output in debug mode
                    'encodeOptions' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                    // ...
                ],
            ],

            'class' => 'yii\web\Response',
            'on beforeSend' => function ($event) {
                $response = $event->sender;

                if ($response->statusCode == 404) {
                    $response->data = [
                        'code' => 404,
                        'message' => 'Not  found',
                    ];
                }

                if ($response->statusCode == 401) {
                    $response->data = [
                        'code' => 401,
                        'message' => 'Authorization Required',
                    ];
                }
            },
        ],


    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['*'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['*'],
    ];
}

return $config;
