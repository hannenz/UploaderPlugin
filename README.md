# UploaderPlugin


__This is a plugin for CakePHP >= 2.0__ (but could be simply rewritten to work with 1.3 as well)


UploaderPlugin is a plugin to be used with the CakePHP framework.
It allows to manage uploads as "hasMany associated objects" related to any of your models.
The plugin provides a behavior to be attached to the model which shall
"have" the uploads and allows for multiple aliased upload types.

Furthermore the plugin provides a helper to output file upload fields
along with a list of yet uploaded files.

Multiple uploads are supported (as long as the browser supports them).

The UploadsController allows to edit uploads (e.g. rename, add title and description, manually set mime type etc.), delete them and of course display them.

Upload form fields are progressively enhanced by JQuery providing AJAX-style seamless uploading, displaying an upload queue and progress on each file.

## Installation

- Drop the plugin into your CakePHP project's Plugin folder (usually at `/app/Plugin`)
- Create the uploads table in your database. You can find a SQL dump in `/Uploader/Config/uploads.sql`
- Create the necessary upload destination directories below `app/webroot` and make sure they PHP has write permissions for them.


## Configuration

In the model(s) which shall "have" uploads, attach the Uploadable behavior:


~~~php

<?php
class YourModel extends AppModel {

	public $actsAs = array(
		'Uploader.Uploadable' => $settings
	);

	...
}
?>

~~~

Settings are very extensive but allow for very flexible configuration of the plugin:

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

~~~php

<?php
$settings = array(
	'uploadAlias' => array(
		'max' => number,					// Max. number of uploads per record
		'maxSize' => number,				// Max. filesize in bytes (0 or unset means: no limit)
		'allow' => array(),					// Array of mime-types that are allowed for upload (e.g. array('image/jpg', 'application/pdf', ...))
		'display' => 'fileAlias' 			// Name of the fileAlias to be used as icon (by default and if not set, will look for a fileAlias containing the string 'thumb' in any form
		'files' => array(					// Array of destination files for each upload. Must at least contain one fileAlias
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
	),
	// You can add any number of uploadAliases here
);

?>
~~~

max
: (numeric, optinal) The maximum number of uploads allowed for each record of your model. If not specified there is no limit. Normally an attempt to upload another file exceeding the max limit, results in a valiudation error, but for the special case when max is set to 1, the existing upload will be overwritten by any subsequent upload.

maxSize
: (numeric, optional) Allows to specify a max filesize in bytes. Remember that there are other limiting settings in php.ini (upload_max_filesize and post_max_size) which can be smaller that your settings!

allow
: (array, optional) Specifies the allowed file types as an array of mime types. If not set, all types are allowed.

display
: (string, optional) UploaderPlugin always sets a path for an icon ready to display, usually a thumbnail or file icon. You can specify the `fileAlias` which shall act as display icon. If this option is not set, the plugin will do: if the upload's type is some kind of image, it will check if there is a `fileAlias` containig the string 'thumb', or else use the first `fileAlias`. If it is not an image it will lookup the icon from `/app/Plugin/Uploader/webroot/img/mime_types` according to the file's type.

files
: (array) This is the array which specifies the upload destination(s). At least one destination must be present! Destinations are specified as array elements with the key being the `fileAlias` name and the value another array containing the `path` and optinally an `action` key. `path` specifies the destination's path relative to the application's webroot (WWW_ROOT), `action` holds an array of actions to perform on each uploaded file for that destination (e.g. make thumbnails etc.) See below for more on actions.

_

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
            [title] => Lorem Ipsum
        )

    [uploadAlias] => Array
        (
            [0] => Array
                (
                    [id] => 1395
                    [created] => 2011-11-22 10:30:43		// Read: 'uploaded'
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

The plugin comes with a Helper which outputs the appropriate file input and a list of available uploads.

For your model's edit action do this:

~~~php

<?php
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

				// Upload errors occured, give a flash message
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
?>

~~~

~~~php

<?php
	// File: /app/View/Foobars/edit.ctp

	echo $this->Form->create('Item', array('type' => 'file'));
	echo $this->Form->input('id');
	echo $this->Form->input('title');

	echo $this->UploaderForm->file('Picture');
	echo $this->UploaderForm->file('Image', array('multiple' => true));
	echo $this->UploaderForm->file('Attachment', array('multiple' => true));
	echo $this->Form->end(__('Submit'));
?>

~~~

That's it. The Helper's file() method outputs markup that behaves accordingly to any other input, including validation (if you don't forget to set 'uploadErrors' in the controller!!)

Furthermore it outputs a list of the uploads that this record has already. The output of this list can be controlled via the `/app/Plugin/Uploader/View/Elements/default_element.ctp` element. There is an option to allow specifying own custom elements but not yet implemented correctly, so for the time being, you need to edit the fie `default_element.ctp`

The UploaderForm::file method takes the `uploadAlias` as first argument and optionally an options array as second argument.

Options are CakePHP-style key/value pairs and can be:

####multiple
whether the upload field shall have the multiple property (modern browsers
provide a multi-select file selector and allow for multiple uploads)

Default: false

####element
You can specify the element to be used to render the upload list...

Default:
default_element.ctp
_
__(This needs improvement!!!)__


####error
Array of validation error messages, give the following keys for the different errors:


<dl>
<dt>maxSize</dt>
<dd>File was too large</dd>
<dt>fileType</dt>
<dd>Invalid filetype</dd>
<dt>max</dt>
<dd>Maximum number of files for this record exceeded</dd>
<dt>isUploadedFile</dt>
<dd>Illegal upload (php's is_uploaded_file() failed)</dd>
<dt>isError</dt>
<dd>Illegal upload (php reported error != 0 in form data)</dd>
</dl>

Default:

~~~php

<?php
array(
	'fileType' => 		__d('uploader', 'This filetype is not allowed', true),
	'maxSize' => 		__d('uploader', 'The file is too large', true),
	'noError' => 		__d('uploader', 'Upload failed', true),
	'isUploadedFile' =>	__d('uploader', 'Upload failed', true),
	'max' => 			__d('uploader', 'Maximum number of uploads exceeded', true)
);
?>

~~~

##Custom Actions

Each uploaded file can be modified by one or more actions. The plugin comes with an ImageComponent which provides common image manipulating actions, namely crop, resize, scale and desaturate.
To provide custom action only needs to implement your own Component (inside the plugin's components folder: /app/Plugin/Uploader/Controller/Component/YourComponent.php).
The component must implement at least the two methods load() which load the original file and save it back after all actions have been applied.
Both methods take the input/ outut file's full path as first argument and must return a boolean value which indicates success of the operation.

Here is an example:

~~~php

<?php
class CustomActionsComponent extends Component {

	function load($filename){
		// Load the unmodified file into memory
		// You want to have a pointer to the file stored somehow like
		// $this->file = file_get_contents($filename)
		// or something similar, so you can access the file from within
		// the actions.

		return ($success);
	}

	function save($filename){
		// Save the file to disk

		return ($success);
	}

	function my_action($options = array()){
		$options = array_merge(array(
				'defaultOption1' => defaultValue1
			),
			$options
		);

		// Do superfancy stuff

		return ($success);
	}
}
?>

~~~

Your custom actions are Component methods which take one array argument as options which are passed for each action like specified in the behavior's settings, so they are key/ value pairs. The example above shows how to provide default settings for option key's that are not set.
For each ComponentName in the behavior's setting the plugin will load the uploaded file _once_ by calling the load method, then call all actions specified inside the component's array and after that saves the file back to its final destination by calling the save() method of the component.


###Credits

Uploaderplugin uses the following wonderful third party code and artwork:
- Mime Type icons taken from [Faenza](http://gnome-look.org/content/show.php/Faenza?content=128143) by Matthieu James
- [jquery.html5_upload](http://code.google.com/p/jquery-html5-upload/) is the work of Mihail.D
- And of course the incredibly awesome [CakePHP](http://www.cakephp.org) framework!

