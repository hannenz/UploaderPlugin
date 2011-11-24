<?php
	// Output a checkbox to allow selecting multiple uploads to delete
	echo $this->Form->input('uploadsToDelete', array(
		'type' => 'checkbox',
		'label' => false,
		'value' => $upload['id']
	));

	echo 'I AM A CUSTOM ELEMENT! >> ' . $upload['name'];
?>
