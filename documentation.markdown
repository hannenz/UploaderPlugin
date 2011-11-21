#Uploader Plugin

##Files

`/app/plugin/uploader/config/uploader_config.php`
: The plugin's configuration file
`/app/plugin/uploader/config/mime_types.php`
: Mime Types by filename extensions (only used as fallback if no other method is available)
`/app/plugin/uploader/config/uploads.sql`
: SQL to create the database table
`/app/plugin/uploader/models/uploads.php`
. The model
`/app/plugin/uploader/controllers/uploads_controller.php`
: The controller
`/app/plugin/uploader/controllers/components/image.php`
: Image manipulation component
`/app/plugin/uploader/views/helpers/uploader.php`
: The helper
`/app/plugin/uploader/views/elements/default_element.ctp`
: A default element to be used to display uploads in lists.

## Installation

- Unzip the archive to `/app/plugins/`
- Create a database table according to the SQL in `uploads.sql`.

##Associations

Set up the associations. A model can have one or many upload(s), so in your model that should have uploads, write:

~~~
	var $hasMany = array(
		'{Alias}' => array(
			'className' => 'Upload',
			'foreignKey' => 'foreign_key',
			'dependent' => true,
			'conditions' => array(
				'{Alias}.alias' => '{Alias}',
				'{Alias}.model' => $this->name,
			),
			'order' => array('Alias.pos' => 'ASC')
		)
	);
~~~

(use $hasOne accordingly)

{Alias} is an alias name you can choose for the uploads, such as 'Image' or 'Attachment'...

##Configuration

The plugin is configured by editing `/plugins/uploader/config/uploader_config.php`. For every {Alias}, specify an array of options under the `UploaderConfig` key:

model
: the model to which the uploads belongs
uniquify
: if true, each upload will be renamed to a unique filename
allow
:array of mime-types to allow for upload, * is allowed to speicfy groups, such as `image/*`
files
: Each upload can result in multiple files. E.g. to make thumbnails etc. For each file specify an array with a destination path (must exist and be writeable of course) and an action-Array: Actions apply to images only and can be one or more of:
	- crop
	- resize
	- grayscale

	Set the Array key to the action's name and then as value specify the parameters (if any) as value or array

##Invocation

To place an upload form in a view, use the UploaderHelper available as `Uploader.Uploader` in the contorller's helpers array:

~~~
echo $this->Uploader->form($alias, $id, $options);
~~~

Options are

title
: Title for the upload form (false, true, string)
label
: The label for the upload's input field (false, string)
infobox
: if true, a info box will be shown, telling the user the max. upload filesize and the allowed file types.
queue
: output a queue for the uploads (nice jquery effects) (true, false)
progress
: output progress (current filename and "x of y")
response
: show a list with the already uploaded files and appends any following uploads to that list (true, false)
redirect
: where to redirect after upload (defaults to referer) (string)
element
: An element's name that will be used to render the list items of the response list. Defaults to `default_element` in the plugin's elements folder, if you specify another one, it should be placed in the application's element folder

That's it
Have fun!
