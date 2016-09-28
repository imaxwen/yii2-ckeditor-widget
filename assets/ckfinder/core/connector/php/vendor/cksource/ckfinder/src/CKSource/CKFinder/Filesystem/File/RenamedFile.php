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
use CKSource\CKFinder\Exception\AlreadyExistsException;
use CKSource\CKFinder\Exception\FileNotFoundException;
use CKSource\CKFinder\Exception\InvalidExtensionException;
use CKSource\CKFinder\Exception\InvalidNameException;
use CKSource\CKFinder\Exception\InvalidRequestException;
use CKSource\CKFinder\Filesystem\Path;
use CKSource\CKFinder\ResourceType\ResourceType;

/**
 * Class RenamedFile
 *
 * Represents file being renamed
 */
class RenamedFile extends ExistingFile
{
    /**
     * New file name
     *
     * @var string $newFileName
     */
    protected $newFileName;

    /**
     * @param string       $newFileName  new file name
     * @param string       $fileName     current file name
     * @param string       $folder       current file folder
     * @param ResourceType $resourceType current file resource type
     * @param CKFinder     $app          CKFinder app
     */
    public function __construct($newFileName, $fileName, $folder, ResourceType $resourceType, CKFinder $app)
    {
        $this->newFileName = $newFileName;

        parent::__construct($fileName, $folder, $resourceType, $app);
    }

    /**
     * Renames current file
     *
     * @return bool true if file was renamed successfully
     *
     * @throws \Exception
     */
    public function doRename()
    {
        $oldPath = Path::combine($this->getPath(), $this->getFilename());
        $newPath = Path::combine($this->getPath(), $this->newFileName);

        $backend = $this->resourceType->getBackend();

        if ($backend->has($newPath)) {
            throw new AlreadyExistsException('Target file already exists');
        }

        $this->deleteThumbnails();
        $this->resourceType->getResizedImageRepository()->renameResizedImages(
            $this->resourceType,
            $this->folder,
            $this->getFilename(),
            $this->newFileName
        );

        $this->getCache()->move(
            Path::combine($this->resourceType->getName(), $this->folder, $this->getFilename()),
            Path::combine($this->resourceType->getName(), $this->folder, $this->newFileName));

        return $backend->rename($oldPath, $newPath);
    }

    /**
     * Validates renamed file
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function isValid()
    {
        if (!$this->exists()) {
            throw new FileNotFoundException();
        }

        $newExtension = pathinfo($this->newFileName, PATHINFO_EXTENSION);

        if (!$this->hasAllowedExtension()) {
            throw new InvalidRequestException('Invalid source file extension');
        }

        if (!$this->resourceType->isAllowedExtension($newExtension)) {
            throw new InvalidExtensionException('Invalid target file extension');
        }

        if (!$this->hasValidFilename() || $this->isHidden()) {
            throw new InvalidRequestException('Invalid source file name');
        }

        if (!File::isValidName($this->newFileName, $this->config->get('disallowUnsafeCharacters')) ||
            $this->resourceType->getBackend()->isHiddenFile($this->newFileName)) {
            throw new InvalidNameException('Invalid target file name');
        }

        return true;
    }
}
