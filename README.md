# UploaderPlugin

__This is a plugin for CakePHP >= 2.0 (but could be simply rewritten to work with 1.3 as well)__

A plugin to be used with CakePHP.
With this plugin, oyu can manage uploads as hasMany associated objects.
The plugin provides a behavior to be attached to the model which shall
"have" the uploads and allows for multiple aliased upload types.

Furthermore the plugin provides a helper to output file upload fields
along with a list of yet uploaded files.

Multiple uploads are supported (as long as the browser supports them).

This is the base version of the plugin. The plan is to improve it by
enhancing it via Javascript to provide ajax-like uploads with progress
etc. - stay tuned

## Configuration

In the model(s) which shall "have" uploads, attach the behavior:

~~~

public $actsAs = array(
	'Uploader.Uploadable' => $settings
);

~~~

Settings are very extensive but allow for very flexible configuration of
the plugin:

Each record of your model can have multiple upload types, which are
identified by a string which is called `uploadAlias`. You need at least
one configured `uploadAlias` in the settings array.

In the next turn, each `uploadAlias` can have multiple files, because
the plugin allows to store several 'versions' of a file, e.g. to have
thumbnails and large images of the same file. To accomplish this, the
plugin allows to take one or more `actions` on each uploaded file. Actions
are in fact Component methods and the UploaderPlugin ships with one
Component, providing common image manipulating tasks.

It is possible, though, to provide custom actions by implementing an
according component. See below for details on this.

Back to the settings for the UploadableBehavior:

~~~
$settings = array(
	'uploadAlias' => array(
		'max' => number,		// Max. number of uploads per record
		'maxSize' => number,	// Max. filesize in bytes (0 or unset means: no limit)
		'allow' => array(),		// Array of mime-types that are allowed for upload (e.g. array('image/jpg', 'application/pdf', ...))
		'display' => 'fileAlias' 	// Name of the fileAlias to be used as icon (by default and if not set, will look for a fileAlias containing the string 'thumb' in any form
		'files' => array(		// Array of destination files for each upload. Must at least contain one fileAlias
			'fileAlias' => array(
				'path' => 'path/to/destination',	// Give a path relative to the application's webroot (not the plugin's!!)
													// Make sure that this path existst and is writable!!
				'action' => array(					// Array of actions to perform on this fileAlias
					'ComponentName' => array(				// The name of the component which implements the action(s)
						'methodName',
						'methodName' => array('parameterName' => $parameterVale)
					)
				)
			),
			// You can add any number of fileAliases here
		)
	)
	// You can add any number of uploadAliases here
);

~~~

## Uploads as hasMany objects

Once the behavior is configured your model "acts as a upload-container".
The association is a standard "hasMany" relation and each find on your
model now receives the according uploads with data like this:

~~~
Array
(
    [YourModel] => Array
        (
            [id] => 26
            [title] => Anything...
        )

    [uploadAlias] => Array
        (
            [0] => Array
                (
                    [id] => 1395
                    [created] => 2011-11-22 10:30:43
                    [modified] => 2011-11-22 10:30:43
                    [filename] => c0a80004-6bc3-bca4.JPG	// This is the actual (real) filename
                    [name] => DSC00140.JPG					// This is the original filename, e.g. the filename how the user uploaded it...
                    [size] => 345081						// The file's size in bytes (stored as reported during upload from PHP, your sizes for different fileAlias may differ...)
                    [type] => image/jpeg					// The file's mime type (UploaderPlugin uses an own detection cascade)
                    [pos] => 1								// Position parameter, e.g. the number of the upload relative to it's siblings
                    [model] => YourModel					// The name of the model this upload belongs to
                    [foreign_key] => 26						// The id of the record this upload belongs to
                    [alias] => uploadAlias					// The uploadAlias this upload is assigned to
                    [session_id] => 						// Session ID is stored in order to implement "pending uploads", see below for more on that...
                    [title] => DSC00140.JPG					// Each upload can have a title and a description
                    [description] => 						// The title is initially set to the file's name, the description is empty by default
                    [poster] => 							// For future usage: For media files, such as video/ audio files it could be nice to have poster images provided... not implemented yet
                    [files] => Array													// The array of files, all paths are relative to WWW_ROOT
                        (
                            [fileAlias1] => /files/thumbnails/c0a80004-6bc3-bca4.JPG
                            [fileAlias2] => /files/c0a80004-6bc3-bca4.JPG
                        )

                    [icon] => /files/thumbnails/c0a80004-6bc3-bca4.JPG					// And because UploaderPlugin is so nice, here is a ready-to-use icon path to use, which shows either the image (if it is one) or a icon according to the file's type
                )

            [1] => Array
                (
                    [id] => 1396
                    . . .
                )
		)
	[anotherUploadAlias] => Array(
		(
			[0] => Array(
				(
					[id] => . . .
				)
			)
		)
	)
)

~~~

## UploaderFormHelper

The plugin comes with a Helper which outputs the appropriate file input
and a list of available uploads.

For fpr your example model's edit action do this:

~~~
// File: /app/Controller/foobars.php

public $helpers = array('Uploader.UploaderForm');

public function edit($id = null) {
	$this->Item->id = $id;
	if (!$this->Item->exists()) {
		throw new NotFoundException(__('Invalid item'));
	}
	if ($this->request->is('post') || $this->request->is('put')) {
		if ($this->Item->save($this->request->data)) {
			$this->Session->setFlash(__('The item has been saved'));

/* HERE IS THE UPLOADER PLUGIN RELEVANT PART --- */

			// Check for upload errors
			if (!empty($this->Item->uploadErrors)){

				// Upload errors occured, give a flash message and re-render the edit view
				$this->Session->setFlash(__('File upload failed'));

				// This one is important!!
				$this->set('uploadErrors', $this->Item->uploadErrors);
			}

/* END OF UPLOADER PLUGIN RELEVANT PART --- */
		}
		else {
			$this->Session->setFlash(__('The item could not be saved. Please, try again.'));
		}
	}
	$this->Item->recursive = 1;
	$this->request->data = $this->Item->read(null, $id);
}

~~~

~~~

// File: /app/View/Foobars/edit.ctp

<?php
	echo $this->Form->create('Item', array('type' => 'file'));?>
	echo $this->Form->input('id');
	echo $this->Form->input('title');

	echo $this->UploaderForm->file('Picture');
	echo $this->UploaderForm->file('Image', array('multiple' => true));
	echo $this->UploaderForm->file('Attachment', array('multiple' => true));
	echo $this->Form->end(__('Submit'));
?>

~~~

That's it. The Helper's file() method outputs markup that behaves accordingly to any other
input, including validation (if you don't forget to set 'uploadErrors' in the controller!!)

Furthermore it outputs a list of the uploads that this record has already.

The UploaderForm::file method takes the uploadAlias as first argument and
optionally an options array as second argument.

Options are cake-style key/ value pairs and can be:

####multiple
whether the upload field shall have the multiple property (modern browsers
provide a multi-select file selector and allow for multiple uploads)

Default: false

####error
Array of validation error messages, give the following keys for the different errors:


maxSize
: file was too large
fileType
: Invalid filetype
max
: Maximum number of files for this record exceeded
isUploadedFile
: Illegal upload (php's is_uploaded_file() failed)
isError
: Illegal upload (php reported error != 0 in form data)


Default:

~~~
array(
				'fileType' => 		__d('uploader', 'This filetype is not allowed', true),
				'maxSize' => 		__d('uploader', 'The file is too large', true),
				'noError' => 		__d('uploader', 'Upload failed', true),
				'isUploadedFile' =>	__d('uploader', 'Upload failed', true),
				'max' => 			__d('uploader', 'Maximum number of uploads exceeded', true)
			)
~~~


####element
You can specify the element to be used to render the upload list...

Default:
default_element.ctp

(This needds improvement!!!)

