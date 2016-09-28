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
use CKSource\CKFinder\Exception\AccessDeniedException;
use CKSource\CKFinder\Exception\InvalidUploadException;
use CKSource\CKFinder\Filesystem\Folder\WorkingFolder;
use Symfony\Component\HttpFoundation\File\UploadedFile as UploadedFileBase;

/**
 * Class UploadedFile
 *
 * Represents uploaded file
 */
class UploadedFile extends File
{
    /**
     * Symfony UploadedFile object
     *
     * @var UploadedFileBase $uploadedFile
     */
    protected $uploadedFile;

    /**
     * WorkingFolder object pointing on folder where file is uploaded
     *
     * @var WorkingFolder $workingFolder
     */
    protected $workingFolder;

    /**
     * Temporary path for uploaded file
     *
     * @var string $tempFilePath
     */
    protected $tempFilePath;

    /**
     * Constructor
     *
     * @param UploadedFileBase $uploadedFile
     * @param CKFinder         $app
     *
     * @throws \Exception if file upload failed
     */
    public function __construct(UploadedFileBase $uploadedFile, CKFinder $app)
    {
        $this->uploadedFile = $uploadedFile;
        $this->workingFolder = $app['working_folder'];

        $this->tempFilePath = tempnam(sys_get_temp_dir(), 'ckf');
        $pathinfo = pathinfo($this->tempFilePath);

        try {
            $uploadedFile->move($pathinfo['dirname'], $pathinfo['basename']);
        } catch (\Exception $e) {
            $errorMessage = $uploadedFile->getErrorMessage();
            switch ($uploadedFile->getError()) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new InvalidUploadException($errorMessage, Error::UPLOADED_TOO_BIG, array(), $e);

                case UPLOAD_ERR_PARTIAL:
                case UPLOAD_ERR_NO_FILE:
                    throw new InvalidUploadException($errorMessage, Error::UPLOADED_CORRUPT, array(), $e);

                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new InvalidUploadException($errorMessage, Error::UPLOADED_NO_TMP_DIR, array(), $e);

                case UPLOAD_ERR_CANT_WRITE:
                case UPLOAD_ERR_EXTENSION:
                    throw new AccessDeniedException($errorMessage, array(), $e);
            }
        }

        parent::__construct($uploadedFile->getClientOriginalName(), $app);
    }

    /**
     * Checks if file was uploaded properly
     *
     * @return bool true if upload is valid
     */
    public function isValid()
    {
        return $this->uploadedFile && $this->tempFilePath && is_readable($this->tempFilePath) && is_writable($this->tempFilePath);
    }

    /**
     * Sanitizes current file name using options set in Config
     */
    public function sanitizeFilename()
    {
        $this->fileName = static::secureName($this->fileName, $this->config->get('disallowUnsafeCharacters'));

        $resourceType = $this->workingFolder->getResourceType();

        if ($this->config->get('checkDoubleExtension')) {
            $pieces = explode('.', $this->fileName);

            $basename = array_shift($pieces);
            $extension = array_pop($pieces);

            foreach ($pieces as $p) {
                $basename .= $resourceType->isAllowedExtension($p) ? '.' : '_';
                $basename .= $p;
            }

            // Add the last extension to the final name.
            $this->fileName = $basename . '.' . $extension;
        }
    }

    /**
     * Checks if file extension is allowed in target folder
     *
     * @return bool true if extension is allowed in target folder
     */
    public function hasAllowedExtension()
    {
        if (strpos($this->fileName, '.') === false) {
            return true;
        }

        return $this->workingFolder->getResourceType()->isAllowedExtension($this->getExtension());
    }

    /**
     * @copydoc File::autorename()
     */
    public function autorename(Backend $backend = null, $path = '')
    {
        return parent::autorename($this->workingFolder->getBackend(), $this->workingFolder->getPath());
    }

    /**
     * Check if file was renamed
     *
     * @return bool true if file was renamed
     */
    public function wasRenamed()
    {
        return $this->fileName != $this->uploadedFile->getClientOriginalName();
    }

    /**
     * Check if current file name is defined as hidden in configuration settings
     *
     * @return bool true if filename is hidden
     */
    public function isHiddenFile()
    {
        return $this->workingFolder->getBackend()->isHiddenFile($this->fileName);
    }

    /**
     * Returns the upload error.
     *
     * If the upload was successful, the constant UPLOAD_ERR_OK is returned.
     * Otherwise one of the other UPLOAD_ERR_XXX constants is returned.
     *
     * @return int upload error
     */
    public function getError()
    {
        return $this->uploadedFile->getError();
    }

    /**
     * Returns the upload error message.
     *
     * @return string upload error
     */
    public function getErrorMessage()
    {
        return $this->uploadedFile->getErrorMessage();
    }

    /**
     * Returns uploaded file contents
     *
     * @return string uploaded file data
     */
    public function getContents()
    {
        return file_get_contents($this->tempFilePath);
    }

    /**
     * Returns contents stream for uploaded file
     *
     * @return resource
     */
    public function getContentsStream()
    {
        return fopen($this->tempFilePath, 'r');
    }

    /**
     * Returns uploaded file size in bytes
     *
     * @return int file size in bytes
     */
    public function getSize()
    {
        clearstatcache();

        return filesize($this->tempFilePath);
    }

    /**
     * Detect HTML in the first KB to prevent against potential security issue with
     * IE/Safari/Opera file type auto detection bug.
     * Returns true if file contain insecure HTML code at the beginning.
     *
     * @return boolean true if uploaded file contains html in first 1024 bytes
     */
    public function containsHtml()
    {
        $fp = fopen($this->tempFilePath, 'rb');
        $chunk = fread($fp, 1024);
        fclose($fp);

        $chunk = strtolower($chunk);

        if (!$chunk) {
            return false;
        }

        $chunk = trim($chunk);

        if (preg_match("/<!DOCTYPE\W*X?HTML/sim", $chunk)) {
            return true;
        }

        $tags = array('<body', '<head', '<html', '<img', '<pre', '<script', '<table', '<title');

        foreach ($tags as $tag) {
            if (false !== strpos($chunk, $tag)) {
                return true ;
            }
        }

        //type = javascript
        if (preg_match('!type\s*=\s*[\'"]?\s*(?:\w*/)?(?:ecma|java)!sim', $chunk)) {
            return true ;
        }

        //href = javascript
        //src = javascript
        //data = javascript
        if (preg_match('!(?:href|src|data)\s*=\s*[\'"]?\s*(?:ecma|java)script:!sim', $chunk)) {
            return true ;
        }

        //url(javascript
        if (preg_match('!url\s*\(\s*[\'"]?\s*(?:ecma|java)script:!sim', $chunk)) {
            return true ;
        }

        return false ;
    }

    /**
     * Checks if file with current extension is allowed to contain any HTML/JS
     *
     * @return bool true if file is allowed to contain HTML chunks
     */
    public function isAllowedHtmlFile()
    {
        return in_array(strtolower($this->getExtension()), $this->config->get('htmlExtensions'));
    }

    /**
     * Checks if file is a valid image
     *
     * Internally `getimagesize` is used for validation
     *
     * @return bool true if file is allowed to contain HTML chunks
     */
    public function isValidImage()
    {
        if (@getimagesize($this->tempFilePath) === false) {
            return false ;
        }

        return true;
    }

    /**
     * Sets given data as new file contents
     *
     * @param string $data new file contents
     */
    public function setContents($data)
    {
        file_put_contents($this->tempFilePath, $data);
    }
}
