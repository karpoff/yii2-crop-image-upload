<?php
/**
 * @copyright Copyright (c) 2014 karpoff
 * @link https://github.com/karpoff/yii2-crop-image-upload
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */
namespace karpoff\icrop;

use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;

/**
 * CropImageUpload renders a jCrop plugin for image crop.
 * @see http://deepliquid.com/content/Jcrop.html
 * @link https://github.com/karpoff/yii2-crop-image-upload
 * @package karpoff\icrop
 */
class CropImageUpload extends InputWidget
{
	/**
	 * @var array the options for the jCrop plugin.
	 * Please refer to the jCrop Web page for possible options.
	 * @see http://deepliquid.com/content/Jcrop_Manual.html
	 */
	public $clientOptions = ['boxWidth' => 450, 'boxHeight' => 400];

	/**
	 * @var string crop ratio
	 * format is width:height where width and height are both floats
	 * if empty and has model, will be got from CropImageBehavior
	 */
	public $ratio;

	/**
	 * @var string attribute name storing crop value or crop value itself if no model
	 * if empty and has model, will be got from CropImageBehavior
	 * crop value has topLeftX-topLeftY-width-height format where all variables are float
	 * all coordinates are in percents of corresponded image dimension
	 */
	public $crop_field;

	/**
	 * @var string crop value
	 * if empty and has model, will be got from $crop_field of model
	 * crop value has topLeftX-topLeftY-width-height format where all variables are float
	 * all coordinates are in percents of corresponded image dimension
	 */
	public $crop_value;

	/**
	 * @var string url where uploaded files are stored
	 * if empty and has model, will be got from CropImageBehavior
	 */
	public $url;

	/**
	 * @var string css class of container that stores image crop
	 */
	public $crop_class = 'crop_medium';

	/**
	 * @inheritdoc
	 */
	public function run()
	{
		if ($this->hasModel()) {
			echo Html::activeFileInput($this->model, $this->attribute, $this->options);

			if (!$this->ratio || !$this->crop_field || !$this->url) {
				foreach ($this->model->getBehaviors() as $beh) {
					if (!empty($beh->attribute) && $beh->attribute == $this->attribute) {
						if ($beh instanceof CropImageUploadBehavior) {
							if (!$this->ratio && $beh->ratio)
								$this->ratio = $beh->ratio;
							if (!$this->crop_field && $beh->crop_field)
								$this->crop_field = $beh->crop_field;
							if (!$this->url && $beh->url)
								$this->url = $beh->url;
							break;
						}
					}
				}
			}

			if (!$this->crop_value && $this->crop_field)
				$this->crop_value = $this->model->{$this->crop_field};
		} else {
			echo Html::fileInput($this->name, $this->value, $this->options);
		}

		$crop_id = false;

		if ($this->crop_field) {
			if ($this->hasModel()) {
				$crop_id = Html::getInputId($this->model, $this->crop_field);
				echo Html::activeHiddenInput($this->model, $this->crop_field, ['value' => $this->crop_value]);
			} else {
				$crop_id = $this->options['id'] . '_' . $this->crop_field;
				echo Html::hiddenInput($this->crop_field, $this->crop_value);
			}
		}

		if ($this->url)
			$this->url = \Yii::getAlias($this->url);

		$jsOptions = [
			'crop_value' => $this->crop_value,
			'crop_id' => $crop_id,
			'ratio' => $this->ratio,
			'url' => $this->url,
			'clientOptions' => $this->clientOptions,
			'is_crop_prev' => ($crop_id || !$this->hasModel()) ? false : true,
			'crop_class' => $this->crop_class,
		];

		$this->registerPlugin($jsOptions);
	}

	/**
	 * Registers jCrop
	 */
	protected function registerPlugin($options)
	{
		$view = $this->getView();
		CropImageUploadAsset::register($view);

		$id = $this->options['id'];

		$view->registerJs("jQuery('#{$id}').cropImageUpload(".json_encode($options).");");
	}
} 