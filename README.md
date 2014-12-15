cropped image upload extension for Yii2
======================


This extension automatically uploads image and make crop.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist karpoff/yii2-crop-image-upload "*"
```

or add

```json
"karpoff/yii2-crop-image-upload": "*"
```

to the `require` section of your `composer.json` file.

Usage
-----

### Upload image and create crop

Attach the behavior in your model:

```php
class Document extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['photo', 'file', 'extensions' => 'jpeg, gif, png', 'on' => ['insert', 'update']],
        ];
    }

    /**
     * @inheritdoc
     */
    function behaviors()
    {
        return [
            [
                'class' => CropImageUploadBehavior::className(),
                'attribute' => 'photo',
                'scenarios' => ['insert', 'update'],
                'path' => '@webroot/upload/docs',
                'url' => '@web/upload/docs',
				'ratio' => 1,
				'crop_field' => 'photo_crop',
				'cropped_field' => 'photo_cropped',
            ],
        ];
    }
}
```

Example view file:

```php
<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
    <?= $form->field($model, 'photo')->widget(CropImageUpload::className()) ?>
    <div class="form-group">
        <?= Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?>
    </div>
<?php ActiveForm::end(); ?>
```
