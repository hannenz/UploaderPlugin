<?php
/* This is the element that is used to display upload error messages
 * when called by AJAX (Without Ajax upload errors appear as standard
 * validation messages),
 *
 * $uploadErrors contains the errors as Array in the form
 *
 * array(
 * 	'field1' => array(
 * 		'violated rule 1',
 * 		'violated rule 2'
 * 	),
 * 	'field2' => array(
 * 		'violated rule...
 * 	)
 *
 * $upload contains the data of the upload that failed.
 */
?>
<div class="uploader error">
	<?php
		$list = '';
		foreach ($uploadErrors as $err){
			foreach ($err as $rule){
				$list .= $this->Html->tag('li', $error[$rule]);
			}
		}

		$upload = array_shift($upload);
		echo __d('uploader', 'Uploading the file `%s` failed:', $upload['name']);
		echo $this->Html->tag('ul', $list);
	?>
</div>
