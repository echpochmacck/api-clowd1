<?php

namespace app\models;

use Yii;
/**
 * This is the model class for table "coauthors".
 *
 * @property int $id
 * @property int $user_id
 * @property int $file_id
 * @property int $role_id
 *
 * @property File $file
 * @property Role $role
 * @property User $user
 */
class Authors extends \yii\db\ActiveRecord
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
            [['user_id', 'file_id', 'role_id'], 'required'],
            [['user_id', 'file_id', 'role_id'], 'integer'],
            [['file_id'], 'unique', 'targetAttribute'=>['file_id', 'user_id']],
            // [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            // [['file_id'], 'exist', 'skipOnError' => true, 'targetClass' => File::class, 'targetAttribute' => ['file_id' => 'id']],
            // [['role_id'], 'exist', 'skipOnError' => true, 'targetClass' => Role::class, 'targetAttribute' => ['role_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'file_id' => 'File ID',
            'role_id' => 'Role ID',
        ];
    }

    /**
     * Gets query for [[File]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFile()
    {
        // return $this->hasOne(File::class, ['id' => 'file_id']);
    }

    public static function isAuthor($role_id)
    {
        $role = Role::find()
        ->where(['id'=> $role_id])
        ->asArray()
        ->all(); 
        return $role[0]['role'] === 'author';
    }

    /**
     * Gets query for [[Role]].
     *
     * @return \yii\db\ActiveQuery
     */
    // public function getRole()
    // {
    //     return $this->hasOne(Role::class, ['id' => 'role_id'])->asArray();
    // }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        // return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
