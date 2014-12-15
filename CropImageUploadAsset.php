<?php

/**
 * @copyright Copyright (c) 2014 karpoff
 * @link https://github.com/karpoff/yii2-crop-image-upload
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace karpoff\icrop;

use yii\web\AssetBundle;

class CropImageUploadAsset extends AssetBundle
{
	public $sourcePath = '@vendor/karpoff/yii2-crop-image-upload/assets';

	public $depends = [
		'yii\web\YiiAsset',
		'yii\web\JqueryAsset'
	];

	public function init()
	{
		$this->css[] = YII_DEBUG ? 'css/jquery.Jcrop.css' : 'css/jquery.Jcrop.min.css';
		$this->js[] = YII_DEBUG ? 'js/jquery.Jcrop.js' : 'js/jquery.Jcrop.min.js';
		$this->js[] = 'js/cropImageUpload.js';
	}
}