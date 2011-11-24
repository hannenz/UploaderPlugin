<?php
/*
 *      upload.php
 *
 *      Copyright 2011 Johannes Braun <me@hannenz.de>
 *
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 *
 *		File: /app/plugins/uploader/models/upload.php
 */


class Upload extends AppModel {
	var $name = 'Upload';


	/* Validation rules */

	var $validate = array(
		'is_uploaded_file' => array(
			'isUploadedFile' => array(
				'rule' => array('comparison', '==', 1),
				'required' => true
			)
		),
		'error' => array(
			'noError' => array(
				'rule' => array('comparison', '==', 0),
				'required' => true
			)
		),
		'pos' => array(
			'max' => array(
				'rule' => 'validateMax',
				'required' => true
			)
		),
		'size' => array(
			'maxSize' => array(
				'rule' => 'validateMaxFileSize',
				'required' => true
			)
		),
		'type' => array(
			'fileType' => array(
				'rule' => 'validateFileType',
				'required' => true
			)
		)
	);

/* Callback
 * Called before an Upload is validated and prepares the data structure
 * to contain upload relevant data.
 *
 * name: beforeValidate
 */
	function beforeValidate(){

		$data = $this->data;
		$config = $this->config;
		$alias = key($data);


		// Assure that foreign_key is numeric
		if (empty($data[$alias]['foreign_key'])){
			$data[$alias]['foreign_key'] = 0;
		}
		// Do we still need this???
		if (empty($this->data[$alias]['id'])){
			$data[$alias]['id'] = null;
		}

		// Check existence of destination dir and write permissions
		$wp = true;
		foreach ($config[$alias]['files'] as $name => $path){
			if (!is_dir($path['path']) || !is_writable($path['path'])){
				$wp = false;
			}
		}

		// Get the file's extension
		$extension = end(explode('.', $data[$alias]['name']));

		// Count existing uploads
		$uploads = $this->find('all', array(
			'conditions' => array(
				$alias.'.alias' => $alias,
				$alias.'.model' => $data[$alias]['model'],
				$alias.'.foreign_key' => $data[$alias]['foreign_key']
			),
			'fields' => array($alias.'.id'),
			'order' => array($alias.'.pos' => 'ASC')
		));
		$nUploads = count($uploads);

		// If we have a 'max=1' configuration, replace the upload, e.g. delete the existing one
		if (!empty($config[$alias]['max']) && $config[$alias]['max'] == 1 && $nUploads == 1){
			// mark the existing upload to be deleted after successful new upload
			$this->deleteMe = $uploads[0][$alias]['id'];
			$nUploads = 0;
		}

		// Do validation stuff, setup fields to be checked by the real
		// validation process etc.
		$data[$alias]['type'] = $this->getFiletype($data[$alias]['tmp_name'], $extension);
		$data[$alias]['is_uploaded_file'] = is_uploaded_file($data[$alias]['tmp_name']);
		$data[$alias]['filename'] = sprintf('%s.%s', $this->uniqueFilename(), $extension);
		$data[$alias]['write_permissions'] = $wp ? 1 : 0;
		$data[$alias]['pos'] = $nUploads + 1;
		$data[$alias]['session_id'] = session_id();
		if (empty($data[$alias]['title'])){
			$data[$alias]['title'] = $data[$alias]['name'];
		}

		// Write back the modified data
		$this->data = $data;
	}

/* Uploads one file
 * Applies any actions to the file and moves it to the
 * destination path.
 *
 * name: upload
 * @param array $data
 * 		Upload data
 *
 * @return boolean
 * 		flags success of the operation
 */
	function beforeSave(){

		$alias = key($this->data);

		if ($alias != 'Upload'){

			$data = $this->data[$alias];
			$config = $this->config[$data['alias']];

			foreach ($config['files'] as $file){
				$full_path = WWW_ROOT . DS . trim($file['path'], '/') . DS . $data['filename'];

				if (isset($file['action']) && count($file['action']) > 0 && in_array($data['type'], array('image/jpeg', 'image/gif', 'image/png'))){
					foreach ($file['action'] as $component => $actionData){

						App::import('Component', 'Uploader.'.$component);
						$name = $component.'Component';
						$Component = new $name(new ComponentCollection);

						$Component->load($data['tmp_name']);

						foreach ($actionData as $key => $value){
							if (is_numeric($key)){
								$method = $value;
								$params = array();
							}
							else {
								$method = $key;
								$params = $value;
							}
							if (method_exists($Component, $method)){
								if (!is_array($params)){
									$params = array($params);
								}
								$Component->{$method}($params);
							}
						}

						$Component->save($full_path, null, 75, 0666);
					}
				}
				else {
					// we checked is_uploaded_file and proper write
					// permissions in beforeValidate already, so
					// move_uploaded_file() SHOULD return no errors
					// here...??!
					@move_uploaded_file($data['tmp_name'], $full_path);
					chmod($full_path, 0666);
				}
			}
		}
		return true;
	}


/* Callback: Called after (successful) upload.
 * Used to delete a replacing upload, which was marked during the
 * beforeValidate() method
 *
 * name: afterSave
 */
	function afterSave($created){
		if (!empty($this->deleteMe)){
			$this->delete($this->deleteMe);
			unset ($this->deleteMe);
		}
	}


/* Callback
 * Delete the upload's files and adjust positions
 *
 * name: beforeDelete
 */
	function beforeDelete(){
		$upload = $this->read(null, $this->id);
		if (!empty($upload)){
			$alias = key($upload);
			$uploadAlias = $upload[$alias]['alias'];

			// Remove the upload's file(s)
			$this->deleteFiles($upload);

			// Adjust positions; find all uploads of the given record with
			// higher position and decrease each pos.
			$uploads = $this->find('all', array(
				'conditions' => array(
					$alias.'.model' => $upload[$alias]['model'],
					$alias.'.alias' => $uploadAlias,
					$alias.'.foreign_key' => $upload[$alias]['foreign_key'],
					$alias.'.pos >' => $upload[$alias]['pos']
				)
			));
			foreach ($uploads as $upload){
				$this->id = $upload[$alias]['id'];
				$this->saveField('pos', $upload[$alias]['pos'] - 1);
			}
		}
		return (true);
	}

/* Deletes all files for the given upload
 *
 * name: deleteFiles
 * @param array	$upload
 * 		The upload
 */
	function deleteFiles($upload){

		$alias = key($upload);
		// In case $alias is 'Upload', we need the real alias
		$uploadAlias = $upload[$alias]['alias'];

		$files = $this->config[$uploadAlias]['files'];
		if (!empty($files) && is_array($files)){
			foreach ($files as $file){
				$delpath = $file['path'] . DS . $upload[$alias]['filename'];
				@unlink($delpath);
			}
		}
	}


/* Return all pending uploads for the given $model, $alias and $id
 *
 * name: get_pending
 * @param string $model optional
 * 		The name of the model
 * @param string $alias optional
 * 		The nameof the upload alias
 *
 * @return array
 *		Array of uploads that have not been assigned to a record yet.
 */
	function getPending($model = null, $alias = null){

		$backAlias = $this->alias;
		$this->alias = 'Upload';

		$conditions = array(
			'Upload.session_id' => session_id(),
			'Upload.foreign_key <=' => 0
		);

		if ($model !== null){
			$conditions['Upload.model'] = $model;
		}
		if ($alias !== null){
			$conditions['Upload.alias'] = $alias;
		}
		$pending = $this->find('all', array('conditions' => $conditions));
		$this->alias = $backAlias;

		$r = array();
		foreach ($pending as $p){
			$r[] = $this->extend(array_shift($p));
		}
		return ($r);
	}

/* Saves all pending uploads by assiging them to the record specified by
 * $model, $alias and $id
 *
 * Pending uploads are uploads that don't have a associated database
 * record yet (e.g. when uploaded inan "Add" Form before the actual
 * entry has been saved.
 * These uploads are marked with a foreign_key set to -1 (or 0). With
 * calling this method we can "collect" all pending uploads matching the
 * given Alias/Model/id.
 *
 * name: savePending
 * @param string $model
 * 		The name of the model to assign the pending uploads to
 * @param string $alias
 * 		The upload alias to assign the pending uploads to
 * @param int $id
 * 		The id of the record (foreign_key) to assign the pending uploads
 * 		to
 */
	function savePending($id, $model = null, $alias = null){
		$pending_uploads = $this->getPending($model, $alias);
		foreach ($pending_uploads as $upload){
			$this->id = $upload['id'];
			$this->saveField('foreign_key', $id, array('validate' => false, 'callbacks' => false));
		}
	}


/* Gets an appropriate icon to display for the upload, If it is an
 * image (MIME Type), then it looks for path aliases like thumb, thumbs,
 * thumbnails etc. this one is used; else the first file path will be
 * used.
 * For non-image files, the appropriate mime_icon is returned
 *
 * name: getIcon
 * @param array $upload
 * 		Upload array (deepest level)
 * @return string
 * 		file path rooted at WEBROOT to icon image file.
 */
	function getIcon($upload){

		$icon = false;
		$alias = $upload['alias'];
		if (!empty($upload['poster'])){
			return ($upload['poster']);
		}

		if (substr($upload['type'], 0, 5) == 'image'){
			//~ For images search if any path name contains the string
			//~ 'thumb', then use this
			$files = $this->config[$alias]['files'];

			if (isset($this->config[$alias]['display']) && isset($files[$this->config[$alias]['display']])){
				$icon = $files[$this->config[$alias]['display']]['path'] . DS . $upload['filename'];
			}
			else {
				foreach ($files as $name => $file){
					if (preg_match('/thumb/i', $name)){
						$icon = DS . $file['path'] . DS . $upload['filename'];
						break;
					}
				}
			}

			// If none was found, use the first path name
			if ($icon === false){
				$first = array_shift($files);
				$icon = $first['path'] . DS . $upload['filename'];
			}
		}
		else {
			// Search for a PNG icon in /icons/mime_types or use
			// application-octet-stream.png as default
			$icon_file_name = str_replace('/', '-', $upload['type']) . '.png';
			$path = APP . join(DS, array('Plugin', 'Uploader', 'webroot', 'img', 'mime_types'));

			if (!file_exists($path . DS . $icon_file_name)){
				$icon_file_name = 'application-octet-stream.png';
			}
			$icon = DS . 'uploader' . DS . 'img' . DS . 'mime_types' . DS . $icon_file_name;
		}

		return (DS . trim($icon, '/'));
	}




/*
 * Detects the mime_type of a given file, trying different methods,
 * depending on what methods are available in the given environment
 *
 * name: getFiletype
 * @param string $path
 * 		Full path of the file
 * @param string $ext
 * 		The filename extension
 * @return string
 * 		Mime Type
 */
	private function getFiletype($path, $ext){
		if (is_readable($path) === false){
			return false;
		}

		// Try finfo
		if (is_callable('finfo_file')){
			if (defined('FILEINFO_MIME_TYPE')){
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
			}
			else{
				$finfo = finfo_open(FILEINFO_MIME);
			}

			$type = finfo_file($finfo, $path);
			finfo_close($finfo);

			return (strtolower($type));
		}

		// Try mime_content_type()
		if (is_callable('mime_content_type')){
			return (strtolower(mime_content_type($path)));
		}

		// try execute shell command "file"
		if (is_callable('shell_exec')){
			$type = shell_exec('file --mime-type -p ' . escapeshellarg($path));

			if ($type !== ''){
				$type = end(explode(' ', $type));
				return trim(strtolower($type));
			}
		}

		// Guess by filename extension
		Configure::load('Uploader.mime_types');
		$types = Configure::read('MimeTypes');
		return ((empty($types[$ext]) === false) ? $types[$ext] : 'application/octet-stream');
	}

/*
 * Check if an upload is allowed
 *
 * name: isAllowed
 * @param string $mime_type
 * 		The mime_type to check
 * @param $allow array
 * 		Allowed types
 * @return boolean
 */
	private function isAllowed($mime_type, $allow){
		if (($ret = empty($allow)) === false){
			foreach ($allow as $item){
				$pattern = sprintf('%%^%s$%%', str_replace('*', '.*', trim($item)));
				if (($ret = (preg_match($pattern, $mime_type)) ? true : false)){
					break;
				}
			}
		}
		return ($ret);
	}

/*
 * Generates a unique filename
 *
 * name: uniqueFilename
 * @return		string: unique filename
 */
	private function uniqueFilename(){
		$ipbits = explode('.', $_SERVER['REMOTE_ADDR']);
		list($usec, $sec) = explode(' ', microtime());
		$usec = (integer) ($usec * 65536);
		$sec = ((integer) $sec) & 0xFFFF;
		$uid = sprintf('%08x-%04x-%04x', ($ipbits[0] << 24) | ($ipbits[1] << 16) | ($ipbits[2] << 8) | $ipbits[3], $sec, $usec);
		return ($uid);
	}


/* Validation methods */

	function validateMaxFileSize($check){
		$alias = key($this->data);
		if (!empty($this->config[$alias]['maxSize']) && $this->config[$alias]['maxSize'] > 0){
			return ($check['size'] <= $this->config[$alias]['maxSize']);
		}
		return (true);
	}

	function validateFileType($check){
		$alias = key($this->data);
		if (!empty($this->config[$alias]['allow'])){
			return ($this->isAllowed($check['type'], $this->config[$alias]['allow']));
		}
		return (true);
	}

	function validateMax($check){
		$alias = key($this->data);
		if ($this->data[$alias]['foreign_key'] == 0){
			return (true);
		}
		if (!empty($this->config[$alias]['max']) && $this->config[$alias]['max'] > 0){
			return ($check['pos'] <= $this->config[$alias]['max']);
		}
		return (true);
	}

/* Upload a poster image
 * Upload image will be resized
 *
 * name: uploadPoster
 * @param $id integer
 * 		The upload id to which the poster shall be uploaded
 * @param $destination
 * 		The detination path relative to APP/webroot
 * @return
 * 		Returns the filename of the poster image relative to
 * 		APP/webroot or null in case of an error
 */
	function uploadPoster($id, $destination){
		$destPath = null;
		if (!empty($_FILES['Poster'])){

			$data = $_FILES['Poster'];

			$destination = trim($destination, DS);
			if (is_uploaded_file($data['tmp_name'])){
				$ext = end(explode('.', $data['name']));
				$type = $this->getFiletype($data['tmp_name'], $ext);
				if ($this->isAllowed($type, array('image/*'))){
					$name = $this->uniqueFilename() . '.' . $ext;
					$destPath = DS . $destination . DS . $name;

					try {
						App::import('Component', 'Uploader.Image');
						$Component = new ImageComponent(new ComponentCollection);

						if (!$Component->load($data['tmp_name'])){
							throw new Exception();
						}
						if (!$Component->resize(array('width' => 600))){
							throw new Exception();
						}
						if (!$Component->save(rtrim(WWW_ROOT, DS) . $destPath)){
							throw new Exception();
						}
					}
					catch (Exception $e){
						if (!@move_uploaded_file($data['tmp_name'], rtrim(WWW_ROOT, DS) . $destPath)){
							$destPath = null;
						}
					}
				}
			}
		}
		return ($destPath);
	}

/* Delete a poster image
 *
 * name: deletePoster
 * @param $id integer 		The id of the upload from which the poster image shall be deleted
 */
	function deletePoster($id){
		$poster = $this->field('poster', array('id' => $id));
		$path = rtrim(WWW_ROOT, DS) . $poster;
		@unlink($path);
		$this->id = $id;
		$this->saveField('poster', '', array('validate' => false, 'callbacks' => false));
	}

	function extend($upload){
		$upload['files'] = array();
		$uploadAlias = $upload['alias'];

		foreach ($this->config[$uploadAlias]['files'] as $fileAlias => $fileData){
			$upload['files'][$fileAlias] = DS . trim($fileData['path'], DS) . DS . $upload['filename'];
		}

		$upload['icon'] = $this->getIcon($upload);
		return ($upload);
	}

}
?>
