<?php

/*
 * CKFinder
 * ========
 * http://cksource.com/ckfinder
 * Copyright (C) 2007-2015, CKSource - Frederico Knabben. All rights reserved.
 *
 * The software, this file and its contents are subject to the CKFinder
 * License. Please read the license.txt file before using, installing, copying,
 * modifying or distribute this file or part of its contents. The contents of
 * this file is part of the Source Code of CKFinder.
 */

namespace CKSource\CKFinder\Filesystem\File;

use CKSource\CKFinder\CKFinder;
use CKSource\CKFinder\Error;
use CKSource\CKFinder\Exception\InvalidUploadException;
use CKSource\CKFinder\Filesystem\Path;
use CKSource\CKFinder\Image;
use CKSource\CKFinder\ResourceType\ResourceType;

/**
 * Class ExistingFile
 *
 * Represents file that already exists in CKFinder and can be
 * pointed using resource type, path and file name
 *
 */
abstract class ExistingFile extends File
{

    /**
     * File resource type
     *
     * @var ResourceType $resourceType
     */
    protected $resourceType;

    /**
     * Resource type relative folder
     *
     * @var string $folder
     */
    protected $folder;

    /**
     * Array for errors that may occur during file processing
     *
     * @var array $errors
     */
    protected $errors = array();

    /**
     * File metadata
     *
     * @var array
     */
    protected $metadata;

    /**
     * Constructor
     *
     * @param string       $fileName
     * @param string       $folder
     * @param ResourceType $resourceType
     * @param CKFinder     $app
     */
    public function __construct($fileName, $folder, ResourceType $resourceType, CKFinder $app)
    {
        $this->folder = $folder;
        $this->resourceType = $resourceType;

        parent::__construct($fileName, $app);
    }

    /**
     * Returns backend relative folder path (i.e. path with prepended resource type directory)
     *
     * @return string backend relative path
     */
    public function getPath()
    {
        return Path::combine($this->resourceType->getDirectory(), $this->folder);
    }

    /**
     * Returns backend relative file path
     *
     * @return string file path
     */
    public function getFilePath()
    {
        return Path::combine($this->getPath(), $this->fileName);
    }

    /**
     * Checks if current file folder path is valid
     *
     * @return bool true if path is valid
     */
    public function hasValidPath()
    {
        return Path::isValid($this->getPath());
    }

    /**
     * Checks if current file has extension allowed in its resource type
     *
     * @return bool true if file has allowed exception
     */
    public function hasAllowedExtension()
    {
        if (strpos($this->fileName, '.') === false) {
            return true;
        }

        $extension = $this->getExtension();

        return $this->resourceType->isAllowedExtension($extension);
    }

    /**
     * Checks if current file is hidden
     *
     * @return bool true if file is hidden
     */
    public function isHidden()
    {
        return $this->resourceType->getBackend()->isHiddenFile($this->fileName);
    }

    /**
     * Checks if current file has hidden path (i.e. if any of the parents folders is hidden)
     *
     * @return bool true if path is hidden
     */
    public function hasHiddenPath()
    {
        return $this->resourceType->getBackend()->isHiddenPath($this->getPath());
    }

    /**
     * Checks if current file exists
     *
     * @return bool true if file exists
     */
    public function exists()
    {
        $filePath = $this->getFilePath();
        $backend = $this->resourceType->getBackend();

        if (!$backend->has($filePath)) {
            return false;
        }

        $fileMetadata = $backend->getMetadata($filePath);

        return isset($fileMetadata['type']) && $fileMetadata['type'] === 'file';
    }

    /**
     * Returns file contents stream
     *
     * @return resource contents stream
     */
    public function getContentsStream()
    {
        $filePath = $this->getFilePath();

        return $this->resourceType->getBackend()->readStream($filePath);
    }

    /**
     * Returns file contents
     *
     * @return resource contents stream
     */
    public function getContents()
    {
        $filePath = $this->getFilePath();

        return $this->resourceType->getBackend()->read($filePath);
    }

    /**
     * Sets new file contents
     *
     * @param string $contents file content
     * @param string $filePath  path to save the file
     *
     * @return bool true if saved successfully
     *
     * @throws \Exception if content size is too big
     */
    public function setContents($contents, $filePath = null)
    {
        $filePath = $filePath ?: $this->getFilePath();

        $maxSize = $this->resourceType->getMaxSize();

        $contentsSize = strlen($contents);

        if ($maxSize && $contentsSize > $maxSize) {
            throw new InvalidUploadException('New file contents is too big for resource type limit', Error::UPLOADED_TOO_BIG);
        }

        $saved = $this->resourceType->getBackend()->put($filePath, $contents);

        if ($saved) {
            $this->deleteThumbnails();
        }

        return $saved;
    }

    /**
     * Adds an error to array of errors of current file
     *
     * @param int $number error number
     *
     * @see Error
     */
    public function addError($number)
    {
        $this->errors[] = array(
            'number' => $number,
            'name'   => $this->getFilename(),
            'type'   => $this->resourceType->getName(),
            'folder' => $this->folder
        );
    }

    /**
     * Returns an array of errors that occurred during file processing
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Removes thumbnail generated for current file
     *
     * @return true if thumbnail was found and deleted
     */
    public function deleteThumbnails()
    {
        $extension = $this->getExtension();

        if (Image::isSupportedExtension($extension) || ($extension === 'bmp' && $this->config->get('thumbnails.bmpSupported'))) {
            $thumbsRepository = $this->resourceType->getThumbnailRepository();

            return $thumbsRepository->deleteThumbnails($this->resourceType, $this->folder, $this->fileName);
        }

        return false;
    }

    /**
     * Removes thumbnail generated for current file
     *
     * @return true if thumbnail was found and deleted
     */
    public function deleteResizedImages()
    {
        $extension = $this->getExtension();

        if (Image::isSupportedExtension($extension)) {
            $resizedImageRepository = $this->resourceType->getResizedImageRepository();

            return $resizedImageRepository->deleteResizedImages($this->resourceType, $this->folder, $this->fileName);
        }

        return false;
    }

    /**
     * Returns last modification time
     *
     * @return int unix timestamp
     */
    public function getTimestamp()
    {
        $metadata = $this->getMetadata();

        return $metadata['timestamp'];
    }

    /**
     * Returns file size
     *
     * @return int size in bytes
     */
    public function getSize()
    {
        $metadata = $this->getMetadata();

        return $metadata['size'];
    }

    /**
     * Returns file metadata
     *
     * @return array
     */
    public function getMetadata()
    {
        if (null === $this->metadata) {
            $filePath = $this->getFilePath();

            $this->metadata = $this->resourceType->getBackend()->getWithMetadata($filePath, array('mimetype', 'timestamp'));
        }

        return $this->metadata;
    }
}
