<?php
	/*
	 * This could be a custom element to render the items
	 * that the UploaderFormHelper outputs when displaying the
	 * list of uploads.
	 *
	 * Pass the element's name as option parameter when calling
	 * UploaderFormHelper::file()
	 *
	 * The $upload variable contains the upload's data
	 */

	// Output a checkbox to allow selecting multiple uploads to delete
	echo $this->Form->input('uploadsToDelete', array(
		'type' => 'checkbox',
		'label' => false,
		'value' => $upload['id']
	));

	// Display upload; do what you want here...
	echo 'I AM A CUSTOM ELEMENT! >> ' . $upload['name'];
?>
