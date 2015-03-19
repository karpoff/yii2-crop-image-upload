(function ($) {

	$.fn.cropImageUpload = function (config) {
		var _obj = $(this);
		_obj.on('change', function() {
			var file = this.files[0];
			var fr = new FileReader();
			fr.onload = function (readerEvt) {
				_image_store.html('');
				$('#' + _obj.attr('id') + '_image').val(Math.random().toString(36).replace(/[^a-z]+/g, '') + '.' + fr.result.split(';')[0].split('/')[1]);
				$.each(config.crops, function() {
					make_crops(this, fr.result);
				});
			};
			fr.readAsDataURL(file);
			return false;
		});

		var _image_store = $('<div>', {'class': 'crop-image-upload-container'}).insertAfter(_obj);

		if (config.crop_class)
			_image_store.addClass(config.crop_class);

		var make_crops = function(crop, src) {
			if (crop.ratio && (crop.image || src)) {
				var cropImage = $('<img>', {
					'src': src ? src : ((config.url ? config.url : '') + '/' + crop.image)
				}).hide();
				_image_store.append(cropImage);

				var cropStore = $('#' + crop.input_id);
				var cropValue = cropStore.val();

				var cropImageW;
				var cropImageH;
				var cropTimeout;

				if (cropValue == '-') {
					cropValue = '';
					cropStore.val(cropValue);
					if (!src) {
						cropImage.show();
						return;
					}
				}

				if (cropValue == '')
					cropValue = ('0-0-100-100');

				function percent (val, main) { return Number((val*100/main).toFixed(2)); }

				var storeCropParams = function(c) {
					cropStore.val(percent(c.x, cropImageW) + '-' + percent(c.y, cropImageH) + '-' + percent(c.x2, cropImageW) + '-' + percent(c.y2, cropImageH));
				};

				var cropParams = {};
				if (config.clientOptions)
					$.extend(cropParams, config.clientOptions);
				cropParams.onSelect = storeCropParams;
				cropParams.onChange = storeCropParams;

				if (crop.ratio) {
					cropParams.aspectRatio = crop.ratio;
				}

				cropImage.Jcrop(cropParams, function(){
					cropImageW = cropImage.width();
					cropImageH = cropImage.height();

					var pp = cropValue.split('-');

					pp[0] = pp[0]*cropImageW/100;
					pp[1] = pp[1]*cropImageH/100;
					pp[2] = pp[2]*cropImageW/100;
					pp[3] = pp[3]*cropImageH/100;
					this.setSelect(pp);
					_image_store.show();
				});
			}
		};

		$.each(config.crops, function() {
			make_crops(this);
		});
	};


})(window.jQuery);
