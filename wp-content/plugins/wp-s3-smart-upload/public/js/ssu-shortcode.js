(function ($) {
  'use strict';
  $(function () {
	$('form.ssu-upload-form').submit(function (evt) {
	  evt.preventDefault();
	  var $inputFile = $(this).find('input.ssu-file');
	  var $submitFile = $(this).find('input[type=submit]');
	  var $message = $(this).parent().find('p.ssu-message');

	  hideMessage($message);

	  var file = $inputFile.prop('files')[0];
	  var postID = $(this).find('input[name=post_id]').val();
	  if (!file) {
		return;
	  }
	  if (!isAllowedFileSize(file)) {
	    alert(window.ssuData.messages.SSU_FILE_MAX_FILE_ERROR);
	    return;
	  }

	  $submitFile.prop('disabled', true);

	  getSignedURL(file.name, file.type, postID)
		.done(function (response) {
		  if (response.signedUrl) {
			uploadFile(response.signedUrl, file, $submitFile, $message);
		  }
		})
		.fail(function(error){
		  $submitFile.prop('disabled', false);
		  showErrorMessage($message);
		});
	});

	$('input.ssu-file').change(function() {
	  var $message = $(this).parent().parent().find('p.ssu-message');
	  hideMessage($message);
	});
  });

  function hideMessage($message) {
	$message.hide();
	$message.removeClass('ssu-error-message ssu-success-message');
  }

  function showErrorMessage($message) {
	var message = $message.data('error');

	$message.css('color', 'red');
	$message.text(message);
	$message.addClass('ssu-error-message');
	$message.show();
  }

  function showSuccessMessage($message) {
	var message = $message.data('success');

	$message.css('color', '');
	$message.text(message);
	$message.addClass('ssu-success-message');
	$message.show();
  }

  function uploadFile(url, data, $submitFile, $message) {
	var xhr = new XMLHttpRequest();
	var oldText = $submitFile.val();

	xhr.addEventListener("readystatechange", function () {
	  if (this.readyState === 4) {
		if (this.status === 200) {
		  showSuccessMessage($message);
		} else {
		  showErrorMessage($message);
		}

		$submitFile.prop('disabled', false);
		$submitFile.val(oldText);
	  }
	});

	xhr.upload.addEventListener('progress', function (evt) {
	  if (evt.lengthComputable) {
		var percentComplete = evt.loaded / evt.total;
		var percentage = Math.round(percentComplete * 100) + "%";
		$submitFile.val(percentage);
	  }
	}, false);

	xhr.open("PUT", url);

	xhr.send(data);
  }

  function getSignedURL(fileName, type, postID) {
    var query = 'objectName=' + encodeURIComponent(fileName) + '&contentType=' + encodeURIComponent(type) + '&post_id=' + postID + '&_wpnonce=' + window.ssuData.nonce;
    var url = window.ssuData.rest_url + 'ssu/v1/s3/pre-signed';
    if (url.indexOf('?') === -1) {
      url = url + '?' + query;
	} else {
	  url = url + '&' + query;
	}

	var settings = {
	  "url": url,
	  "method": "PUT",
	  "timeout": 5000,
	};

	return $.ajax(settings);
  }

  function isAllowedFileSize(file) {
	if (window.ssuData && !window.ssuData.enable_restriction) {
	  return true;
	}

	const oneMbToBye = 1000000;
	return file.size <= window.ssuData.max_file_size * oneMbToBye;
  }

})(jQuery);
