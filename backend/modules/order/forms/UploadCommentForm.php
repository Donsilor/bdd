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
            [['file'], 'file', 'skipOnEmpty' => false, 'checkExtensionByMimeType' => false, 'extensions' => ['xlsx']],
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
        $file = \Yii::getAlias('@storage').'/backend/orderComment/' . $this->file->baseName . '.' . $this->file->extension;

        if(file_exists($file)) {
            $this->addError('file', '有相同文件名文件已经上传');
            return false;
        }

        if ($this->validate()) {
            $this->file->saveAs($file);
            return $file;
        } else {
            return false;
        }
    }
}