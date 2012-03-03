<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Ingmar Schlecht <ingmar@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


/**
 * Representation of a specific usage of a file with possibilities to override certain
 * properties of the original file just for this usage of the file.
 *
 * It acts as a decorator over the original file in the way that most method calls are
 * directly passed along to the original file object.
 *
 * All file related methods are directly passed along; only meta data functionality is adopted
 * in this decorator class to priorities possible overrides for the metadata for this specific usage
 * of the file.
 *
 * @author Ingmar Schlecht <ingmar@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_file_FileReference implements t3lib_file_FileInterface {

	/**
	 * Various properties of the FileReference. Note that these information can be different
	 * to the ones found in the originalFile.
	 *
	 * @var array
	 */
	protected $propertiesOfFileReference;

	/**
	 * The identifier of this file to identify it on the storage.
	 * On some drivers, this is the path to the file, but drivers could also just
	 * provide any other unique identifier for this file on the specific storage.
	 *
	 * @var string
	 */
	protected $uidOfFileReference;

	/**
	 * The file name of this file. It's either the fileName of the original underlying file,
	 * or the overlay file name supplied by the user for this particular usage (FileReference) of the file.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The FileRepository object. Is needed e.g. for the delete() method to delete the usage record
	 * (sys_file_reference record) of this file usage.
	 *
	 * @var t3lib_file_Repository_FileRepository
	 */
	protected $fileRepository;

	/**
	 * Reference to the original File object underlying this FileReference.
	 *
	 * @var t3lib_file_File
	 */
	protected $originalFile;

	/**
	 * Constructor for a file in use object. Should normally not be used directly, use the corresponding factory methods instead.
	 *
	 * @param array $fileReferenceData
	 * @param t3lib_file_Factory $factory
	 */
	public function __construct(array $fileReferenceData, $factory = NULL) {
		$this->propertiesOfFileReference = $fileReferenceData;

		if (!$fileReferenceData['uid_local']) {
			throw new Exception('Incorrect reference to original file given for FileReference.', 1300098528);
		}

		if (!$factory) {
			/** @var $factory t3lib_file_Factory */
			$factory = t3lib_div::makeInstance('t3lib_file_Factory');
		}

		$this->originalFile = $factory->getFileObject($fileReferenceData['uid_local']);

		$this->fileRepository = t3lib_div::makeInstance('t3lib_file_Repository_FileRepository');

		if (!is_object($this->originalFile)) {
			throw new Exception('Original File not found for FileReference.', 1300098529);
		}

		$this->name = $fileReferenceData['name'] !== '' ? $fileReferenceData['name'] : $this->originalFile->getName();
	}


	/*******************************
	 * VARIOUS FILE PROPERTY GETTERS
	 *******************************/


	/**
	 * Returns true if the given key exists for this file.
	 *
	 * @param string $key The property to be looked up
	 * @return boolean
	 */
	public function hasProperty($key) {
		return array_key_exists($key, $this->propertiesOfFileReference);
	}

	/**
	 * Gets a property.
	 *
	 * @param string $key The property to be looked up
	 * @return mixed
	 * @throws InvalidArgumentException
	 */
	public function getProperty($key) {
		if (!$this->hasProperty($key)) {
			throw new InvalidArgumentException('Property "' . $key . '" was not found.', 1314226805);
		}

		return $this->propertiesOfFileReference[$key];
	}

	/**
	 * Gets all properties.
	 *
	 * @return array
	 */
	public function getProperties() {
		return t3lib_div::array_merge_recursive_overrule(
			$this->originalFile->getProperties(),
			$this->propertiesOfFileReference
		);
	}

	/**
	 * Returns the name of this file
	 *
	 * @return string
	 */
	public function getName() {
		return $this->originalFile->getName();
	}

	/**
	 * Returns the title text to this image
	 *
	 * TODO: Possibly move this to the image domain object instead
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->propertiesOfFileReference['title'] ? $this->propertiesOfFileReference['title'] : $this->originalFile->getName();
	}

	/**
	 * Returns the alternative text to this image
	 *
	 * TODO: Possibly move this to the image domain object instead
	 *
	 * @return string
	 */
	public function getAlternative() {
		return $this->propertiesOfFileReference['alternative'] ? $this->propertiesOfFileReference['alternative'] : $this->originalFile->getName();
	}

	/**
	 * Returns the description text to this file
	 *
	 * TODO: Possibly move this to the image domain object instead
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->propertiesOfFileReference['description'];
	}

	/**
	 * Returns the link that should be active when clicking on this image
	 *
	 * TODO: Move this to the image domain object instead
	 *
	 * @return string
	 */
	public function getLink() {
		return $this->propertiesOfFileReference['link'];
	}

	/**
	 * Returns the uid of this File In Use
	 *
	 * @return integer
	 */
	public function getUid() {
		return $this->propertiesOfFileReference['uid'];
	}

	/**
	 * Returns the size of this file
	 *
	 * @return integer
	 */
	public function getSize() {
		return $this->originalFile->getSize();
	}

	/**
	 * Returns the Sha1 of this file
	 *
	 * @return string
	 */
	public function getSha1() {
		return $this->originalFile->getSha1();
	}


	/**
	 * Get the file extension of this file
	 *
	 * @return string The file extension
	 */
	public function getExtension() {
		return $this->originalFile->getExtension();
	}

	/**
	 * Get the MIME type of this file
	 *
	 * @return array file information
	 */
	public function getMimeType() {
		return $this->originalFile->getMimeType();
	}

	/**
	 * Returns the modification time of the file as Unix timestamp
	 *
	 * @return integer
	 */
	public function getModificationTime() {
		$this->originalFile->getModificationTime();
	}

	/**
	 * Returns the creation time of the file as Unix timestamp
	 *
	 * @return integer
	 */
	public function getCreationTime() {
		$this->originalFile->getCreationTime();
	}

	/**
	 * Returns the fileType of this file
	 *
	 * @return integer $fileType
	 */
	public function getType() {
		return $this->originalFile->getType();
	}


	/******************
	 * CONTENTS RELATED
	 ******************/

	/**
	 * Get the contents of this file
	 *
	 * @return string File contents
	 */
	public function getContents() {
		return $this->originalFile->getContents();
	}

	/**
	 * Replace the current file contents with the given string
	 *
	 * @param string $contents The contents to write to the file.
	 * @return t3lib_file_File The file object (allows chaining).
	 */
	public function setContents($contents) {
		return $this->originalFile->setContents($contents);
	}


	/****************************************
	 * STORAGE AND MANAGEMENT RELATED METHDOS
	 ****************************************/


	/**
	 * Get the storage the original file is located in
	 *
	 * @return t3lib_file_Storage
	 */
	public function getStorage() {
		return $this->originalFile->getStorage();
	}

	/**
	 * Returns the identifier of the underlying original file
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return $this->originalFile->getIdentifier();
	}


	/**
	 * Returns a combined identifier of the underlying original file
	 *
	 * @return string Combined storage and file identifier, e.g. StorageUID:path/and/fileName.png
	 */
	public function getCombinedIdentifier() {
		return $this->originalFile->getCombinedIdentifier();
	}

	/**
	 * Deletes only this particular FileReference from the persistence layer (database table sys_file_reference)
	 * but leaves the original file untouched.
	 *
	 * @return boolean TRUE if deletion succeeded
	 */
	public function delete() {
		// TODO: Implement this function. This should only delete the FileReference (sys_file_reference) record, not the file itself.
		throw new Exception('Function not implemented FileReference::delete().');
		return $this->fileRepository->removeUsageRecord($this);
	}

	/**
	 * Renames the fileName in this particular usage.
	 *
	 * @param string $newName The new name
	 * @return t3lib_file_FileReference
	 */
	public function rename($newName) {
		// TODO: Implement this function. This should only rename the FileReference (sys_file_reference) record, not the file itself.
		throw new Exception('Function not implemented FileReference::rename().');
		return $this->fileRepository->renameUsageRecord($this, $newName);
	}


	/*****************
	 * SPECIAL METHODS
	 *****************/

	/**
	 * Returns a publicly accessible URL for this file
	 *
	 * WARNING: Access to the file may be restricted by further means, e.g. some web-based authentication. You have to take care of this
	 * yourself.
	 *
	 * @param bool  $relativeToCurrentScript   Determines whether the URL returned should be relative to the current script, in case it is relative at all (only for the LocalDriver)
	 *
	 * @return string
	 */
	public function getPublicUrl($relativeToCurrentScript = FALSE) {
		return $this->originalFile->getPublicUrl($relativeToCurrentScript);
	}

	/**
	 * Returns TRUE if this file is indexed.
	 * This is always true for FileReference objects, as they rely on a sys_file_reference record to be present,
	 * which in turn can only exist if the original file is indexed.
	 *
	 * @return boolean
	 */
	public function isIndexed() {
		return TRUE;
	}

	/**
	 * Returns a path to a local version of this file to process it locally (e.g. with some system tool).
	 * If the file is normally located on a remote storages, this creates a local copy.
	 * If the file is already on the local system, this only makes a new copy if $writable is set to TRUE.
	 *
	 * @param boolean $writable Set this to FALSE if you only want to do read operations on the file.
	 * @return string
	 */
	public function getForLocalProcessing($writable = TRUE) {
		return $this->originalFile->getForLocalProcessing($writable);
	}

	/**
	 * Returns an array representation of the file.
	 * (This is used by the generic listing module vidi when displaying file records.)
	 *
	 * @return array Array of main data of the file. Don't rely on all data to be present here, it's just a selection of the most relevant information.
	 */
	public function toArray() {
		$array = array_merge(
			$this->originalFile->toArray(),
			$this->propertiesOfFileReference
		);
		return $array;
	}

	/**
	 * Gets the original file being referenced.
	 *
	 * @return t3lib_file_File
	 */
	public function getOriginalFile() {
		return $this->originalFile;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/file/FileReference.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/file/FileReference.php']);
}

?>