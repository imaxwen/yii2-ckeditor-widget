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

namespace CKSource\CKFinder\Event;

use CKSource\CKFinder\CKFinder;
use CKSource\CKFinder\Filesystem\File\EditedFile;

/**
 * EditFileEvent event class
 */
class EditFileEvent extends CKFinderEvent
{
    /**
     * @var EditedFile $uploadedFile
     */
    protected $editedFile;

    /**
     * @var string $newContents
     */
    protected $newContents;

    /**
     * Constructor
     *
     * @param CKFinder   $app
     * @param EditedFile $editedFile
     * @param string     $newContents new file contents
     */
    public function __construct(CKFinder $app, EditedFile $editedFile, $newContents)
    {
        $this->editedFile = $editedFile;
        $this->newContents = $newContents;

        parent::__construct($app);
    }

    /**
     * @return EditedFile
     */
    public function getEditedFile()
    {
        return $this->editedFile;
    }

    /**
     * Returns new contents for edited file
     *
     * @return string
     */
    public function getNewContents()
    {
        return $this->newContents;
    }

    /**
     * Sets new contents for edited file
     *
     * @param string $newContents
     */
    public function setNewContents($newContents)
    {
        $this->newContents = $newContents;
    }
}

