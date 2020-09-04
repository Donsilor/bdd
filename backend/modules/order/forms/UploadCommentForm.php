<?php

namespace backend\modules\order\forms;

use yii\base\Model;
use yii\web\UploadedFile;

class UploadCommentForm extends Model
{
    /**
     * @var UploadedFile
     */
    public $file;

    public function rules()
    {
        return [
            [['file'], 'file', 'skipOnEmpty' => false, 'extensions' => 'xlsx'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'file' => '导入文件',
        ];
    }

    public function upload()
    {
        if ($this->validate()) {
            $this->file->saveAs(\Yii::getAlias('@storage').             'backend/orderComment/' . $this->file->baseName . '.' . $this->file->extension);
            return true;
        } else {
            return false;
        }
    }
}