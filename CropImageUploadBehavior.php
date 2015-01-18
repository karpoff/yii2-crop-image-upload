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
use yii\base\InvalidConfigException;
use yii\db\BaseActiveRecord;
use yii\imagine\Image;

class CropImageUploadBehavior extends UploadBehavior
{
	/**
	 * @var string attribute that stores crop value
	 * if empty, crop value is got from attribute field
	 */
	public $crop_field;
	/**
	 * @var string attribute that stores cropped image name
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

	private $crop_value;
	private $crop_changed;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		if ($this->crop_field === null xor $this->cropped_field === null) {
			throw new InvalidConfigException('The crop_field and cropped_field properties must be both filled or both unfilled.');
		}
	}

	/**
	 * @inheritdoc
	 */
	public function beforeValidate()
	{
		/** @var BaseActiveRecord $model */
		$model = $this->owner;
		if (empty($this->crop_field)) {
			$this->crop_value = $model->getAttribute($this->attribute);
			$this->crop_changed = !empty($this->crop_value);
		} else {
			$this->crop_value = $model->getAttribute($this->crop_field);
			$this->crop_changed = $model->isAttributeChanged($this->crop_field);
		}

		parent::beforeValidate();
	}

	/**
	 * @inheritdoc
	 */
	public function beforeSave()
	{
		parent::beforeSave();

		if ($this->crop_changed && !empty($this->cropped_field)) {
			$this->delete($this->cropped_field, true);
			/** @var BaseActiveRecord $model */
			$model = $this->owner;
			$name = $model->getAttribute($this->attribute);
			if (empty($name))
				$model->setAttribute($this->attribute, $model->getOldAttribute($this->attribute));

			$model->setAttribute($this->cropped_field, $this->getCropFileName($model->getAttribute($this->attribute)));
		}
	}
	/**
	 * @inheritdoc
	 */
	public function afterSave()
	{
		parent::afterSave();

		if ($this->crop_changed) {
			$this->createCrop();
		}
	}
	/**
	 * this method crops the image
	 */
	protected function createCrop()
	{
		$path = $this->getUploadPath($this->attribute);
		$image = Image::getImagine()->open($path);

		$save_path = empty($this->cropped_field) ? $path : $this->getUploadPath($this->cropped_field);

		$crop = explode('-', $this->crop_value);

		$size = $image->getSize();

		foreach ($crop as $ind => $cr) {
			$crop[$ind] = round($crop[$ind]*($ind%2 == 0 ? $size->getWidth() : $size->getHeight())/100);
		}

		$crop_image = $image->crop(new Point($crop[0], $crop[1]), new Box($crop[2]-$crop[0], $crop[3]-$crop[1]));

		if ($this->crop_width)
			$crop_image = $crop_image->resize(new Box($this->crop_width, $this->crop_width / $this->ratio));

		$crop_image->save($save_path);
	}

	/**
	 * @param $filename
	 * @return string
	 */
	protected function getCropFileName($filename)
	{
		return uniqid().'_'. $filename;
	}
}