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
use CKSource\CKFinder\Exception\FileNotFoundException;
use CKSource\CKFinder\Exception\InvalidExtensionException;
use CKSource\CKFinder\Exception\InvalidNameException;
use CKSource\CKFinder\Filesystem\Folder\WorkingFolder;

/**
 * Class DownloadedFile
 *
 * Represents downloaded file
 */
class DownloadedFile extends ExistingFile
{
    /**
     * @var WorkingFolder $workingFolder
     */
    protected $workingFolder;

    /**
     * Constructor
     *
     * @param string        $fileName
     * @param CKFinder      $app
     */
    public function __construct($fileName, CKFinder $app)
    {
        $this->workingFolder = $app['working_folder'];

        parent::__construct($fileName, $this->workingFolder->getClientCurrentFolder(), $this->workingFolder->getResourceType(), $app);
    }

    /**
     * Validates the downloaded file
     *
     * @throws \Exception
     */
    public function isValid()
    {
        if (!$this->hasValidFilename()) {
            throw new InvalidNameException('Invalid file name');
        }

        if (!$this->hasAllowedExtension()) {
            throw new InvalidExtensionException();
        }

        if ($this->isHidden() || !$this->exists()) {
            throw new FileNotFoundException();
        }

        return true;
    }

    /**
     * Checks if file has allowed extension
     *
     * @return bool true if extension is allowed
     */
    public function hasAllowedExtension()
    {
        if (strpos($this->fileName, '.') === false) {
            return true;
        }

        $extension = $this->getExtension();

        return $this->workingFolder->getResourceType()->isAllowedExtension($extension);
    }

    /**
     * Checks if file is hidden
     *
     * @return bool true if file is hidden
     */
    public function isHidden()
    {
        return $this->workingFolder->getBackend()->isHiddenFile($this->fileName);
    }

    /**
     * Checks if file exists
     *
     * @return bool true if file exists
     */
    public function exists()
    {
        return $this->workingFolder->containsFile($this->fileName);
    }
}
