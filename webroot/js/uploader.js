// uploader.js
//
// Copyright 2011 Johannes Braun <me@hannenz.de>
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
// MA 02110-1301, USA.
//
//
function insertSelectAllButton(){
	$('.uploader-list.uploader-can-delete').each(function(i, el){
		var list = $(el);
		var submit = list.siblings('.submit').first();


		var selectAllButton = $('<input type="button" value="Select all" />');

		selectAllButton
			.click(function(){
				list.find('input[type=checkbox]').attr('checked', 'checked');
			})
			.appendTo(submit)
		;

		var invertButton = $('<input type="button" value="Invert selection" />');
		invertButton
			.click(function(){
				list.find('input[type=checkbox]').each(function(){
					if ($(this).attr('checked')){
						$(this).removeAttr('checked');
					}
					else {
						$(this).attr('checked', 'checked');
					}
				});
			})
			.appendTo(submit)
		;

		submit.find('input[type=submit]').click(function(){
			var message = $('input[name="data[Upload][confirm_message]"]').first().val();
			var n = $(this).parents('form').find('input:checked').length;
			var noun;
			if (n > 0){
				if (n == 1){
					noun = $('input[name="data[Upload][singular]"]').first().val();
				}
				else {
					noun = $('input[name="data[Upload][plural]"]').first().val();
				}
				message = message.replace('%n_files%', n);
				message = message.replace('%noun%', noun);
				return (confirm(message));

			}
			return (false);
		});

		if (list.children().length == 0){
			submit.hide();
			return;
		}

	});
}

// Detects support for this kind of stuff
//
// name: has_html5_upload_support
// @return boolean
//
function has_html5_upload_support(){
	var input = document.createElement('input');
	input.type = 'file';
	return ('multiple' in input && typeof File != "undefined" && typeof (new XMLHttpRequest()).upload != "undefined");
}

$(document).ready(function(){

	if (!has_html5_upload_support()){
		//alert ('OMG! You are using a browser without HTML5 Upload support! Go updating!');
		return;
	}

	$('.uploader-progress').hide();
	$('.uploader-submit-button').remove();

	insertSelectAllButton();

	$('.uploader-upload-field').each(function(){
		var container = $(this).parents('.uploader').first();

		$(this).html5_upload({
			url : '/uploader/uploads/add',
			autostart : true,
			autoclear : true,
			sendBoundary : true,
			fieldName : 'upload',
			onStart : on_start,
			onStartOne : on_start_one,
			onProgress : on_progress,
			onFinishOne : on_finish_one,
			onFinish : on_finish
		});

		function on_start(event, total, files){

			// Clear error messages and progress
			container.find('.uploader-errors').html('');
			container.find('.uploader-progress > span').html('');
			container.find('.uploader-progressbar').css('width', '0%');
			container.find('.uploader-progress').show();

			/* Add files to queue */
			container.find('.uploader-queue').each(function(){
				var queue = $(this);
				$(files).each(function(n, file){
					var li = $('<li />');
					var name = $('<span class="uploader-queue-filename">' + file.fileName + '</span>');
					var perc = $('<span class="uploader-queue-perc">0.00%</span>');
					var bar = $('<div class="uploader-queue-progressbar"><div class="uploader-progressbar"></div></div>');
					li
						.addClass('uploader-queue-item-' + n)
						.addClass('uploader-status-uploading')
						.append(name)
						.append(perc)
						.append(bar)
					;
					queue.append(li);
				});
			});
			return (true);
		}

		function on_start_one(event, name, number, total){

			/* Add list item to uploader-list */
			container.find('.uploader-list').each(function(){
				var li = $('<li />');
				li
					.addClass('upload-' + number)
					.addClass('uploader-status-uploading')
					.html(name)
				;

				$(this).append(li);

				/* If it is a "hasOne" upload, mark and hide the old item - if any - in case the upload fails */
				if (container.find('input[name="data[Upload][id]"]').val() > 0){
					$(this).find('li').first().addClass('uploader-old').hide();
				}
			});

			/* Update queue item */
			container.find('.uploader-queue .uploader-queue-item-' + number)
				.removeClass('uploader-status-pending')
				.addClass('uploader-status-uploading')
			;

			container.find('.uploader-progress .uploader-progress-filename').html(name);
			container.find('.uploader-progress .uploader-progress-numbers').html((number + 1) + '/' + total);
			return (true);
		}

		function on_progress(event, progress, name, number, total){
			var perc = (progress * 100).toFixed(2) + '%';
			var queueItem = container.find('.uploader-queue-item-' + number);
			queueItem.find('.uploader-queue-percent').html(perc);
			queueItem.find('.uploader-progressbar').css('width', perc);
		}

		function on_finish_one(event, response, name, number, total){
			var r = $.parseJSON(response);

			var cssClass = r.success ? 'uploader-status-success' : 'uploader-status-error';
			var element = container.find('.uploadElement').val();

			if (r.success){
				container.find('.uploader-queue-item-' + number).delay(10).fadeOut(function(){ $(this).remove()});
				container.find('.uploader-list').each(function(){
					$.get('/uploader/uploads/get_one/' + r.id + '/' + element, function(response){
						container.find('.upload-' + number)
							.html($('<input type="checkbox" name="data[Upload][' + r.id + ']" value="1" />' + response))
							.removeClass('uploader-status-uploading')
							.addClass('uploader-status-success')
						;
					});
					$(this).siblings('.submit').show();
				});
				if (container.find('input[name="data[Upload][id]"]').val() > 0){
					// If upload succeeded and it was a hasOne upload, then remove the old upload-list item
					container.find('.uploader-old').remove();
				}
			}
			else {
				container.find('.upload-' + number).remove();
				container.find('.uploader-queue-item-' + number).remove();
				container.find('.uploader-errors').append($('<li>' + r.message + '</li>'));

				/* If hasOne : Restore old list item */
				container.find('.uploader-old').removeClass('uploader-old').show();
			}

			container.find('.uploader-progress-progressbar .uploader-progressbar').css('width', ((number + 1) / total * 100).toFixed(2) + '%');

			return (true);
		}

		function on_finish(){
			container.find('.uploader-progress-filename').html('Done');
			container.find('.uploader-progress-numbers').html('');
			container.find('.uploader-progress').delay(3000).fadeOut();
		}
	});


});
