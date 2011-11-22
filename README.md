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

