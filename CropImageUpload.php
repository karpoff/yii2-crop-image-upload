<?php
/**
 * @copyright Copyright (c) 2014 karpoff
 * @link https://github.com/karpoff/yii2-crop-image-upload
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */
namespace karpoff\icrop;

use yii\base\InvalidConfigException;
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
	 * if has model, will be got from CropImageBehavior
	 */
	public $ratio;

	/**
	 * @var string attribute name storing crop value or crop value itself if no model
	 * if has model, will be got from CropImageBehavior
	 * crop value has topLeftX-topLeftY-width-height format where all variables are float
	 * all coordinates are in percents of corresponded image dimension
	 */
	public $crop_field;

	/**
	 * @var string crop value
	 * if has model, will be got from $crop_field of model
	 * crop value has topLeftX-topLeftY-width-height format where all variables are float
	 * all coordinates are in percents of corresponded image dimension
	 */
	public $crop_value;

	/**
	 * @var string css class of container that stores image crop
	 */
	public $crop_class = 'crop_medium';



	/**
	 * @var string url where uploaded files are stored
	 * if empty and has model, will be got from CropImageBehavior
	 */
	public $url;


	/**
	 * @inheritdoc
	 */
	public function run()
	{
		$jsOptions = [
			'clientOptions' => $this->clientOptions,
			'crop_class' => $this->crop_class,
		];

		if ($this->hasModel()) {
			echo Html::activeInput('file', $this->model, $this->attribute, $this->options);

			$crops = null;
			foreach ($this->model->getBehaviors() as $beh) {
				if (!empty($beh->attribute) && $beh->attribute == $this->attribute && $beh instanceof CropImageUploadBehavior) {
					$crops = $beh->getConfigurations();
					$this->url = $beh->url;
					break;
				}
			}

			if (!$crops)
				throw new InvalidConfigException("CropImageUploadBehavior is not found for {$this->attribute} attribute");

			$jsOptions['crops'] = [];
			$input_name = Html::getInputName($this->model, $this->attribute);
			$input_id = Html::getInputId($this->model, $this->attribute);

			echo Html::hiddenInput($input_name . '[file]', Html::getAttributeValue($this->model, $this->attribute), ['id' => $input_id . '_image']);

			foreach ($crops as $ind => $crop) {
				$crop_id = $input_id . '_crop' . $ind;
				echo Html::hiddenInput($input_name . '[' . $ind . ']', $crop['value'] === false ? '-' : $crop['value'], ['id' => $crop_id]);

				$jsOptions['crops'][] = [
					'input_id' => $crop_id,
					'ratio' => $crop['ratio'],
					'image' => $crop['image'],
				];
			}

		} else {
			echo Html::fileInput($this->name, $this->value, $this->options);

			$crop_id = (isset($this->options['id']) ? $this->options['id'] : ($this->name . '_id')) . '_' . $this->crop_field;;
			echo Html::hiddenInput($this->crop_field, $this->crop_value, ['id' => $crop_id]);

			$jsOptions['crops'][] = [
				'input_id' => $crop_id,
				'ratio' => $this->ratio,
			];
		}

		if ($this->url)
			$this->url = \Yii::getAlias($this->url);

		$jsOptions['url'] = $this->url;


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