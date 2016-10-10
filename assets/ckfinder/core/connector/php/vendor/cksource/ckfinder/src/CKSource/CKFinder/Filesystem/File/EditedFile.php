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
use CKSource\CKFinder\Exception\InvalidNameException;
use CKSource\CKFinder\Exception\InvalidRequestException;
use CKSource\CKFinder\Filesystem\Folder\WorkingFolder;
use CKSource\CKFinder\Filesystem\Path;


/**
 * Class EditedFile
 *
 * Represents an existing file being edited, i.e. content
 * of the file is going to be replaced with new content.
 */
class EditedFile extends ExistingFile
{
    /**
     * @var WorkingFolder
     */
    protected $workingFolder;

    /**
     * @var string
     */
    protected $newFileName;

    protected $saveAsNew = false;

    public function __construct($fileName, CKFinder $app, $newFileName = null)
    {
        $this->workingFolder = $app['working_folder'];
        $this->newFileName = $newFileName;

        parent::__construct($fileName, $this->workingFolder->getClientCurrentFolder(), $this->workingFolder->getResourceType(), $app);
    }

    public function isValid()
    {
        if (!$this->saveAsNew && !$this->exists()) {
            throw new FileNotFoundException();
        }

        if ($this->newFileName) {
            if (!File::isValidName($this->newFileName, $this->config->get('disallowUnsafeCharacters'))) {
                throw new InvalidNameException('Invalid file name');
            }

            if ($this->workingFolder->containsFile($this->newFileName)) {
                throw new AlreadyExistsException('File already exists');
            }

            if ($this->resourceType->getBackend()->isHiddenFile($this->newFileName)) {
                throw new InvalidRequestException('New provided file name is hidden');
            }
        }

        if (!$this->hasValidFilename() || !$this->hasValidPath()) {
            throw new InvalidRequestException('Invalid filename or path');
        }

        if ($this->isHidden() || $this->hasHiddenPath()) {
            throw new InvalidRequestException('Edited file is hidden');
        }

        return true;
    }

    public function getNewFilename()
    {
        return $this->newFileName;
    }

    public function saveAsNew($saveAsNew)
    {
        $this->saveAsNew = $saveAsNew;
    }

    public function setContents($contents, $filePath = null)
    {
        return parent::setContents($contents, $this->newFileName ? Path::combine($this->getPath(), $this->newFileName) : null);
    }
}
