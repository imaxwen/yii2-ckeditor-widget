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

use CKSource\CKFinder\Backend\Backend;
use CKSource\CKFinder\CKFinder;
use CKSource\CKFinder\Error;
use CKSource\CKFinder\Exception\FileNotFoundException;
use CKSource\CKFinder\Exception\InvalidRequestException;
use CKSource\CKFinder\Filesystem\Path;
use CKSource\CKFinder\ResourceType\ResourceType;
use CKSource\CKFinder\Filesystem\Folder\WorkingFolder;

/**
 * Class CopiedFile
 *
 * Represents copied file
 */
class CopiedFile extends ExistingFile
{
    /**
     * @var WorkingFolder
     */
    protected $targetFolder;

    /**
     * Constructor
     *
     * @param string       $fileName     source file name
     * @param string       $folder       copied source file resource type relative path
     * @param ResourceType $resourceType source file resource type
     * @param CKFinder     $app          CKFinder
     */
    public function __construct($fileName, $folder, ResourceType $resourceType, CKFinder $app)
    {
        $this->targetFolder = $app['working_folder'];

        parent::__construct($fileName, $folder, $resourceType, $app);
    }

    /**
     * Checks if file has allowed extension in both source and target ResourceTypes
     *
     * @return bool true if file has allowed extension in source and target directories
     */
    public function hasAllowedExtension()
    {
        if (strpos($this->fileName, '.') === false) {
            return true;
        }

        $extension = $this->getExtension();

        return parent::hasAllowedExtension() &&
               $this->targetFolder->getResourceType()->isAllowedExtension($extension);
    }

    /**
     * Checks if copied file size doesn't exceed file size limit set for target folder
     *
     * @return bool
     */
    public function hasAllowedSize()
    {
        $filePath = $this->getFilePath();
        $backend = $this->resourceType->getBackend();

        if (!$backend->has($filePath)) {
            return false;
        }

        $fileMetadata = $backend->getMetadata($filePath);

        $fileSize = $fileMetadata['size'];

        $maxSize = $this->targetFolder->getResourceType()->getMaxSize();

        if ($maxSize && $fileSize > $maxSize) {
            return false;
        }

        return true;
    }

    /**
     * @copydoc File::autorename()
     */
    public function autorename(Backend $backend = null, $path = '')
    {
        return parent::autorename($this->targetFolder->getBackend(), $this->targetFolder->getPath());
    }

    /**
     * Copies current file
     *
     * @param string $copyOptions defines copy options in case if file already exists
     *                            in target directory:
     *                            - autorename - renames current file (see File::autorename())
     *                            - overwrite - overwrites existing file
     *
     * @return bool true if file was copied successfully
     *
     * @throws \Exception
     */
    public function doCopy($copyOptions)
    {
        $originalFileStream = $this->getContentsStream();
        $originalFileName = $this->getFilename();

        // Don't copy file to itself
        if ($this->targetFolder->getBackend() === $this->resourceType->getBackend() &&
            $this->targetFolder->getPath() === $this->getPath()) {
            $this->addError(Error::SOURCE_AND_TARGET_PATH_EQUAL);

            return false;
        // Check if file already exists in target backend dir
        } elseif ($this->targetFolder->containsFile($this->getFilename()) && strpos($copyOptions, 'overwrite') === false) {
            if (strpos($copyOptions, 'autorename') !== false) {
                $this->autorename();
            } else {
                $this->addError(Error::ALREADY_EXIST);

                return false;
            }
        }

        if ($this->targetFolder->putStream($this->getFilename(), $originalFileStream)) {
            $resizedImageRepository = $this->resourceType->getResizedImageRepository();
            $resizedImageRepository->copyResizedImages(
                $this->resourceType, $this->folder, $originalFileName,
                $this->targetFolder->getResourceType(), $this->targetFolder->getClientCurrentFolder(), $this->getFilename()
            );

            $this->getCache()->copy(
                Path::combine($this->resourceType->getName(), $this->folder, $originalFileName),
                Path::combine($this->targetFolder->getResourceType()->getName(), $this->targetFolder->getClientCurrentFolder(), $this->getFilename())
            );

            return true;
        } else {
            $this->addError(Error::ACCESS_DENIED);

            return false;
        }
    }

    /**
     * Validates copied file
     *
     * @return bool true if copied file is valid and ready to be copied
     *
     * @throws \Exception
     */
    public function isValid()
    {
        if (!$this->hasValidFilename() || !$this->hasValidPath()) {
            throw new InvalidRequestException('Invalid filename or path');
        }

        if (!$this->hasAllowedExtension()) {
            $this->addError(Error::INVALID_EXTENSION);

            return false;
        }

        if ($this->isHidden() || $this->hasHiddenPath()) {
            throw new InvalidRequestException('Copied file is hidden');
        }

        if (!$this->exists()) {
            $this->addError(Error::FILE_NOT_FOUND);

            return false;
        }

        if (!$this->hasAllowedSize()) {
            $this->addError(Error::UPLOADED_TOO_BIG);

            return false;
        }

        return true;
    }
}
