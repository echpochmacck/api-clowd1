<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;;


/**
 * This is the model class for table "user".
 *
 * @property int $id
 * @property string $token
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password
 *
 * @property File[] $files
 * @property File[] $files0
 */
class Users extends \yii\db\ActiveRecord implements IdentityInterface
{
    const SCENARIO_REGISTER = 'register';
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    public function validatePassword($password) 
    {
        return Yii::$app->getsecurity()->validatePassword($password, $this->password);
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['token', 'first_name', 'last_name', 'email', 'password'], 'string', 'max' => 255],
            [['first_name', 'last_name', 'email', 'password'], 'required', 'on' => static::SCENARIO_REGISTER],
            [['password', 'email'], 'required'],

            [['first_name', 'last_name', 'email', 'password'], 'string', 'max' => 255],
            [['email'], 'unique', 'on' => static::SCENARIO_REGISTER],
            [['email'], 'email'],
            [['token'], 'unique'],
            [['password'], 'match', 'pattern' => '/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])[A-Za-z0-9]{3,}$/u', 'on' => static::SCENARIO_REGISTER],

        ];
        
    }
    /**
     * {@inheritdoc}
     *
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'first_name' => 'Имя',
            'last_name' => 'Фамилия',
            'email' => 'Email',
            'password' => 'Пароль',
        ];
    }

    /**
     * Gets query for [[Files]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFiles()
    {
        // return $this->hasMany(File::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[Files0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFiles0()
    {
        // return $this->hasMany(File::class, ['co_author' => 'email']);
    }

    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * Finds an identity by the given token.
     *
     * @param string $token the token to be looked for
     * @return IdentityInterface|null the identity object that matches the given token.
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['token' => $token]);
    }

    /**
     * @return int|string current user ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|null current user auth key
     */
    public function getAuthKey()
    {
        // return $this->auth_key;
    }

    /**
     * @param string $authKey
     * @return bool|null if auth key is valid for current user
     */
    public function validateAuthKey($authKey)
    {
        // return $this->getAuthKey() === $authKey;
    }


    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['register_at'],
                    // ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                // if you're using datetime instead of UNIX timestamp:
                'value' => new Expression('NOW()'),
            ],
        ];
    }
}
