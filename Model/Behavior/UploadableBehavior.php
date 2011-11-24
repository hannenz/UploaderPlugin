<?php
/*
 * uploadable.php
 *
 * Copyright 2011 Johannes Braun <me@hannenz.de>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 * File: /app/plugins/uploader/models/behaviors/uploadable.php
 *
 * Uploader Plugin: Uploadable behavior
 * This class extends your model's class as it adds the necessary
 * associations and callbacks for behaving according to the Uploader
 * plugin.
 *
 */


class UploadableBehavior extends ModelBehavior {

/* Initialize the behavior
 *
 * Stores the behaviors settings and binds each uploadAlias
 * as a hasMany association to the model the behavior is attached to.
 * Loads an instance of the Upload model
 *
 * name: setup
 */
	function setup(Model $Model, $settings){
		if (!isset($this->settings[$Model->alias])){
			$this->settings[$Model->alias] = array(
				// Default setting
			);
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array)$settings);

		foreach ($this->settings[$Model->alias] as $uploadAlias => $data){
			$this->settings[$Model->alias][$uploadAlias]['model'] = $Model->alias;
			$type = (isset($this->settings[$Model->alias][$uploadAlias]['max']) && $this->settings[$Model->alias][$uploadAlias]['max'] == 1)
				? 'hasMany'
				: 'hasMany'
			;
			$Model->bindModel(array(
				$type => array(
					$uploadAlias => array(
						'className' => 'Uploader.Upload',
						'foreignKey' => 'foreign_key',
						'conditions' => array(
							$uploadAlias . '.alias' => $uploadAlias,
							$uploadAlias . '.model' => $Model->name
						),
						'dependent' => false,
						'order' => array($uploadAlias . '.pos' => 'ASC')
					)
				)
			), false);
		}
		App::uses('Upload', 'Uploader.Model');
		$this->Upload = new Upload();
		$this->Upload->config = $this->settings[$Model->alias];
	}

/* Callback
 * Normalizes input data to contain one array of file data regardless
 * if the file input was a "multiple" or not; then calls the
 * Upload model's save method for each data set to save the upload.
 *
 * Errors during upload operation are stored in the model's $uploadErrors
 * property
 *
 * If an upload has been taken place, the model's property $wasUploading
 * is set to true
 *
 * If a deletion has taken place, the model's property $wasDeleting is set
 * to true
 *
 * name: beforeSave
 */
	function beforeSave($Model){

		$Model->uploadErrors = null;
		$Model->wasUploading = false;
		$Model->wasDeleting = false;

		if (!empty($Model->data['UploadsToDelete'])){
			foreach ($Model->data['UploadsToDelete'] as $id => $delete){
				if ($delete > 0){
					$Model->wasDeleting = true;
					$this->Upload->delete($id);
				}
			}
		}

		if (empty($_FILES)){
			return (true);
		}

		foreach ($_FILES as $uploadAlias => $fileData){
			if (!empty($fileData['name'])){
				$uploads = array();
				if (is_array($fileData['name'])){
					if (empty($fileData['name'][0])){
						continue;
					}
					foreach ($fileData['name'] as $n => $value){
						$uploads[] = array(
							'name' => $fileData['name'][$n],
							'tmp_name' => $fileData['tmp_name'][$n],
							'type' => $fileData['type'][$n],
							'size' => $fileData['size'][$n],
							'error' => $fileData['error'][$n]
						);
					}
				}
				else {
					if (empty($fileData['name'])){
						continue;
					}
					$uploads[] = array(
						'name' => $fileData['name'],
						'type' => $fileData['type'],
						'tmp_name' => $fileData['tmp_name'],
						'size' => $fileData['size'],
						'error' => $fileData['error']
					);
				}
				$errors = array();
				$Model->wasUploading = count($uploads) > 0;
				foreach ($uploads as $upload){
					$upload['alias'] = $uploadAlias;
					$upload['model'] = $Model->name;
					$upload['foreign_key'] = isset($Model->data[$Model->name]['id']) ? $Model->data[$Model->name]['id'] : null;

					$this->Upload->alias = $uploadAlias;
					$this->Upload->create();
					if (!$this->Upload->save($upload)){
						$errors[$uploadAlias][$upload['name']] = $this->Upload->validationErrors;
					}
				}
				if (!empty($errors)){
					$Model->uploadErrors = $errors;
				}
			}
		}

		return (true);
	}


/* Callback
 * Assigns any pending uploads to the record that has been saved
 *
 * name: afterSave
 */
	function afterSave(&$Model, $created){
		if ($created){
			$this->Upload->savePending($Model->id);
		}
	}

/* Callback
 * Deletes any uploads that belong to the record which is going to be
 * deleted. Removes all according files, too (in the Upload model).
 *
 * name: beforeDelete
 */
	function beforeDelete($Model, $cascade = true){

		foreach ($this->settings[$Model->alias] as $uploadAlias => $data){
			$conditions = array(
				'Upload.model' => $Model->name,
				'Upload.alias' => $uploadAlias,
				'Upload.foreign_key' => $Model->id
			);

			$records = $this->Upload->find('all', array('conditions' => $conditions, 'fields' => array('id')));
			if (!empty($records)){
				foreach ($records as $record){
					$this->Upload->delete($record['Upload']['id']);
				}
			}
		}
		return (true);
	}

/* Callback
 * Extends the data for each upload contained by the record by
 * fileAlias data and icon path.
 *
 * name: afterFind
 */
	function afterFind($Model, $results, $primary = false){
		if ($primary){
			foreach ($results as $n => $result){
				foreach ($this->settings[$Model->alias] as $uploadAlias => $setting){
					if (isset($result[$uploadAlias])){
						foreach ($result[$uploadAlias] as $m => $data){
							$results[$n][$uploadAlias][$m] = $this->Upload->extend($data);
						}
					}
				}

			}
		}
		return ($results);
	}

}
