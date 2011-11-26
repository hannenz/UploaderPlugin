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

	$('.input.file.uploader').each(function(){
		var container = $(this);

		var a = container.attr('id').split('_');
		var model = a[1];
		var uploadAlias = a[2];
		var foreignKey = a[3];
		var element = container.find('input.uploader-element').val();

		var fileInput = $(this).find('input[type=file]');
		var form = $(this).parents('form').first();

		fileInput.html5_upload({
			url : function(){
				return ('/uploader/uploads/add/'+model+'/'+uploadAlias+'/'+foreignKey+'/'+element);
			},
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

			var queue = $('<ul />');
			queue.addClass('uploader-queue');

			/* Add files to queue */
			$(files).each(function(n, file){
				var li = $('<li />');
				var name = $('<span class="uploader-queue-filename">' + file.fileName + '</span>');
				var perc = $('<span class="uploader-queue-perc">0.00%</span>');
				var bar = $('<div class="uploader-queue-progressbar"><div class="uploader-progressbar" style="width:0%"></div></div>');
				li
					.addClass('uploader-queue-item-' + n)
					.addClass('uploader-status-pending')
					.append(name)
					.append(perc)
					.append(bar)
				;
				queue.append(li);
			});
			queue.insertBefore(container.find('.uploader-list'));
			return (true);
		}

		function on_start_one(event, name, number, total){
			return (true);
		}

		function on_progress(event, progress, name, number, total){
			var perc = (progress * 100).toFixed(2) + '%';
			var queueItem = container.find('.uploader-queue-item-' + number);

			queueItem
				.removeClass('uploader-status-pending')
				.addClass('uploader-status-uploading')
			;
			queueItem.find('.uploader-queue-perc').html(perc);
			queueItem.find('.uploader-progressbar').css('width', perc);
		}

		function on_finish_one(event, response, name, number, total){
			var queueItem = container.find('.uploader-queue-item-' + number);
			var listItem = $('<li>'+response+'</li>');

			queueItem
				.removeClass('uploader-status-uploading')
				.addClass('uploader-status-finished')
				.delay(3000)
				.fadeOut(function(){
					$(this).remove();
				})
			;

			var list = container.find('.uploader-list');
			if (list.hasClass('uploader-replace') && !$(response).hasClass('error') && list.children().length > 0){
				list.find('li:first').replaceWith(listItem);
			}
			else {
				list.append(listItem);
			}

			if ($(response).hasClass('error')){
				listItem.css('cursor', 'pointer').click(function(){
					$(this).remove();
				});
			}

			return (true);
		}

		function on_finish(){
			container.find('.uploader-queue').fadeOut(function(){
				$(this).remove()}
			);
		}
	});
});
