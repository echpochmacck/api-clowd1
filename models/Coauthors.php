<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "coauthors".
 *
 * @property int $co_auth_id
 * @property int $file_id
 *
 * @property User $coAuth
 * @property File $file
 */
class Coauthors extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'coauthors';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['co_auth_id', 'file_id'], 'required'],
            [['co_auth_id', 'file_id'], 'integer'],
            [['co_auth_id'], 'unique','targetAttribute'=>['co_auth_id','file_id']]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'co_auth_id' => 'Co Auth ID',
            'file_id' => 'File ID',
        ];
    }

    /**
     * Gets query for [[CoAuth]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCoAuth()
    {
        return $this->hasOne(User::class, ['id' => 'co_auth_id']);
    }

    /**
     * Gets query for [[File]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFile()
    {
        return $this->hasOne(File::class, ['id' => 'file_id']);
    }
}
