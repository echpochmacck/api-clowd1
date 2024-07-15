<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;;
use app\models\Users;

/**
 * This is the model class for table "file".
 *
 * @property int $id
 * @property string $file_id
 * @property string $extension
 * @property string $name
 * @property int $user_id
 * @property string $created_at
 * @property int $updated_at
 * @property int $new_field
 * @property string $co_author
 *
 * @property User $coAuthor
 * @property User $user
 */
class Files extends \yii\db\ActiveRecord
{
    public $file;
    // public $name;    

    const SCENARIO_EDIT = 'edit';
    const SCENARIO_CO = 'coAuth';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'file';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // [['file_id', 'extension', 'name', 'user_id', 'created_at', 'updated_at', 'new_field', 'co_author'], 'safe'],
            [['user_id'], 'integer'],
            [['created_at', 'name', 'updated_at'], 'safe'],
            [['name'], 'unique', 'targetAttribute'=>['name','user_id']],
            [['file_id', 'extension', 'url'], 'string', 'max' => 255],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['user_id' => 'id']],
            [['co_author'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['co_author' => 'email']],
            [['file'], 'file', 'extensions'=> ['doc', 'pdf', 'docx', 'zip', 'jpeg', 'jpg'], 'maxSize'=>2*1024*1024],

            [['name'], 'string', 'max'=>255 ],
            [['co_author'], 'integer', 'max'=>255 ,'on'=>static::SCENARIO_CO],
            [['co_author'], 'required','on'=>static::SCENARIO_CO],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'file_id' => 'File ID',
            'extension' => 'Расширение',
            'name' => 'Имя',
            'user_id' => 'User ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'co_author' => 'Co Author',
        ];
    }

    /**
     * Gets query for [[CoAuthor]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCoAuthor()
    {
        return $this->hasOne(Users::class, ['email' => 'co_author']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(Users::class, ['id' => 'user_id']);
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],

                ],
                // if you're using datetime instead of UNIX timestamp:
                'value' => new Expression('NOW()'),
            ],
        ];
    }

}
