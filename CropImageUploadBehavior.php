<?php
/**
 * @copyright Copyright (c) 2014 karpoff
 * @link https://github.com/karpoff/yii2-crop-image-upload
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

namespace karpoff\icrop;

use Imagine\Image\Box;
use Imagine\Image\Point;
use mongosoft\file\UploadBehavior;
use yii\db\BaseActiveRecord;
use yii\imagine\Image;

class CropImageUploadBehavior extends UploadBehavior
{
	/**
	 * @var string attribute that stores crop value
	 * if empty, crop can be changed by reloading image only
	 * make sense only if cropped_field is set
	 */
	public $crop_field;
	/**
	 * @var string attribute that stores cropped image name
	 * if empty, only cropped image is stored
	 */
	public $cropped_field;

	/**
	 * @var string sets width (in pixels) of cropped image
	 */
	public $crop_width;

	/**
	 * @var string crop ratio (needed width / needed height)
	 */
	public $ratio;

	/**
	 * @var array options used while saving image
	 * @see http://imagine.readthedocs.org/en/latest/usage/introduction.html#save-images
	 */
	public $save_options = [];


	/**
	 * @var Array multiple crops
	 * array with multiple crop settings. values are
	 * cropped_field, crop_field, crop_width, ratio, save_options
	 * @see description of CropImageUploadBehavior fields
	 */
	public $crops;

	/**
	 * @var array the scenarios in which the behavior will be triggered
	 */
	public $scenarios = ['default'];

	private $crops_internal;

	/**
	 * return array with list of configurations
	 */
	public function getConfigurations() {
		if ($this->crops_internal === null) {
			/** @var BaseActiveRecord $model */
			$model = $this->owner;

			if (!empty($this->crops)) {
				$this->crops_internal = $this->crops;
			} else {
				$o = [];
				foreach (['cropped_field', 'crop_field', 'crop_width', 'ratio'] as $f) {
					if ($this->$f) {
						$o[$f] = $this->$f;
					}
				}
				$this->crops_internal = [$o];
			}

			foreach ($this->crops_internal as &$crop) {
				if (empty($crop['cropped_field'])) {
					$crop['value'] = false;
					$crop['image'] = $model->getOldAttribute($this->attribute);
				} else if (empty($crop['crop_field'])) {
					$crop['value'] = false;
					$crop['image'] = $model->getAttribute($crop['cropped_field']);
				} else {
					$crop['value'] = $model->getAttribute($crop['crop_field']);
					$crop['image'] = $model->getOldAttribute($this->attribute);
				}
			}
		}
		return $this->crops_internal;
	}

	/**
	 * @inheritdoc
	 */
	public function beforeValidate()
	{
		/** @var BaseActiveRecord $model */
		$model = $this->owner;

		if (in_array($model->scenario, $this->scenarios) && ($crops = $model->getAttribute($this->attribute)) && is_array($crops)) {

			$image_changed = (!$model->getOldAttribute($this->attribute) && $crops['file']) || ($model->getOldAttribute($this->attribute) != $crops['file']);

			$this->getConfigurations();
			foreach ($this->crops_internal as $ind => &$crop) {
				$crop['value'] = $crops[$ind];
				if ($crops[$ind] == '-')
					$crops[$ind] = '';
				if (empty($crop['crop_field'])) {
					$crop['_changed'] = !empty($crops[$ind]);
				} else {
					$crop['_changed'] = $crops[$ind] != $model->getAttribute($crop['crop_field']);
					$model->setAttribute($crop['crop_field'], $crops[$ind]);
				}
				if ($image_changed)
					$crop['_changed'] = true;
				else if ($model->getOldAttribute($this->attribute) == null)
					$crop['_changed'] = false;
			}

			$model->setAttribute($this->attribute, $crops['file']);
		}

		parent::beforeValidate();
	}

	/**
	 * @inheritdoc
	 */
	public function beforeSave()
	{
		parent::beforeSave();

		/** @var BaseActiveRecord $model */
		$model = $this->owner;

		if (in_array($model->scenario, $this->scenarios)) {

			$original = $model->getAttribute($this->attribute);
			if (!$original)
				$original = $model->getOldAttribute($this->attribute);

			foreach ($this->getConfigurations() as $crop) {
				if (isset($crop['_changed']) && $crop['_changed'] && !empty($crop['cropped_field'])) {
					$this->delete($crop['cropped_field'], true);
					if (!empty($crop['cropped_field']))
						$model->setAttribute($crop['cropped_field'], $this->getCropFileName($original));
				}
			}
		}
	}
	/**
	 * @inheritdoc
	 */
	public function afterSave()
	{
		parent::afterSave();

		if (in_array($this->owner->scenario, $this->scenarios)) {
			$image = null;

			foreach ($this->getConfigurations() as $crop) {
				if (isset($crop['_changed']) && $crop['_changed']) {
					if (!$image) {
						$path = $this->getUploadPath($this->attribute);
						if (!$path)
							$path = $this->getUploadPath($this->attribute, true);
						$image = Image::getImagine()->open($path);
					}
					$this->createCrop($crop, $image->copy());
				}
			}
		}
	}
	/**
	 * this method crops the image
	 * @param Array $crop crop config
	 * @param \Imagine\Gd\Image $image
	 */
	protected function createCrop($crop, $image)
	{
		if (!empty($crop['cropped_field'])) {
			$save_path = $this->getUploadPath($crop['cropped_field']);
		} else {
			$save_path = $this->getUploadPath($this->attribute);
		}

		$sizes = explode('-', $crop['value']);

		$real_size = $image->getSize();

		foreach ($sizes as $ind => $cr) {
			$sizes[$ind] = round($sizes[$ind]*($ind%2 == 0 ? $real_size->getWidth() : $real_size->getHeight())/100);
		}

		$crop_image = $image->crop(new Point($sizes[0], $sizes[1]), new Box($sizes[2]-$sizes[0], $sizes[3]-$sizes[1]));

		if (!empty($crop['crop_width']))
			$crop_image = $crop_image->resize(new Box($crop['crop_width'], $crop['crop_width'] / $crop['ratio']));

		$crop_image->save($save_path, isset($crop['save_options']) ? $crop['save_options'] : $this->save_options);
	}

	/**
	 * @param $filename
	 * @return string
	 */
	protected function getCropFileName($filename)
	{
		return uniqid(rand(0, 9999)).'_'. $filename;
	}
}