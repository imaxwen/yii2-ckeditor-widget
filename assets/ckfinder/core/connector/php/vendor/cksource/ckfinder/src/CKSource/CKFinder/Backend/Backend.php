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

namespace CKSource\CKFinder\Backend;

use CKSource\CKFinder\Acl\AclInterface;
use CKSource\CKFinder\Acl\Permission;
use CKSource\CKFinder\Config;
use CKSource\CKFinder\Filesystem\Path;
use CKSource\CKFinder\ResourceType\ResourceType;
use CKSource\CKFinder\Utils;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Plugin\GetWithMetadata;
use League\Flysystem\Filesystem;
use League\Flysystem\AwsS3v2\AwsS3Adapter;

/**
 * Backend filesystem class
 *
 * Wrapper class for League\Flysystem\Filesystem with
 * CKFinder customizations
 */
class Backend extends Filesystem
{
    /**
     * Acl
     *
     * @var AclInterface $acl
     */
    protected $acl;

    /**
     * Config
     *
     * @var Config $ckConfig
     */
    protected $ckConfig;

    /**
     * Backend configuration array
     */
    protected $backendConfig;

    /**
     * Constructor
     *
     * @param array            $backendConfig backend configuration node
     * @param AclInterface     $acl           CKFinder ACL
     * @param Config           $ckConfig      CKFinder Config
     * @param AdapterInterface $adapter       adapter
     * @param null             $config        config
     */
    public function __construct(array $backendConfig, AclInterface $acl, Config $ckConfig, AdapterInterface $adapter, $config = null)
    {
        $this->backendConfig = $backendConfig;
        $this->acl = $acl;
        $this->ckConfig = $ckConfig;

        parent::__construct($adapter, $config);

        $this->addPlugin(new GetWithMetadata());
    }

    /**
     * Returns a path based on resource type and resource type relative path
     *
     * @param ResourceType $resourceType resource type
     * @param string       $path         resource type relative path
     *
     * @return string path to be used with backend adapter
     */
    public function buildPath(ResourceType $resourceType, $path)
    {
        return Path::combine($resourceType->getDirectory(), $path);
    }

    /**
     * Returns a filtered list of directories for given resource type and path
     *
     * @param ResourceType $resourceType
     * @param string       $path
     * @param bool         $recursive
     *
     * @return array
     */
    public function directories(ResourceType $resourceType, $path = '', $recursive = false)
    {
        $directoryPath = $this->buildPath($resourceType, $path);
        $contents = $this->listContents($directoryPath, $recursive);

        // A temporary fix to disable folders renaming for AWS-S3 adapter
        $isAws3 = $this->adapter instanceof AwsS3Adapter;

        foreach ($contents as &$entry) {
            $entry['acl'] = $this->acl->getComputedMask($resourceType->getName(), Path::combine($path, $entry['basename']));

            if ($isAws3) {
                $entry['acl'] &= ~Permission::FOLDER_RENAME;
            }
        }

        return array_filter($contents, function ($v) {
            return isset($v['type']) &&
                   $v['type'] === 'dir' &&
                   !$this->isHiddenFolder($v['basename']) &&
                   $v['acl'] & Permission::FOLDER_VIEW;
        });
    }

    /**
     * Returns a filtered list of files for given resource type and path
     *
     * @param ResourceType $resourceType
     * @param string       $path
     * @param bool         $recursive
     *
     * @return array
     */
    public function files(ResourceType $resourceType, $path = '', $recursive = false)
    {
        $directoryPath = $this->buildPath($resourceType, $path);
        $contents = $this->listContents($directoryPath, $recursive);

        return array_filter($contents, function($v) use ($resourceType) {
            return isset($v['type']) &&
                   $v['type'] === 'file' &&
                   !$this->isHiddenFile($v['basename']) &&
                   $resourceType->isAllowedExtension(isset($v['extension']) ? $v['extension'] : '');
        });
    }

    /**
     * Check if directory under given path contains subdirectories
     *
     * @param ResourceType $resourceType
     * @param string       $path
     *
     * @return bool true if directory contains subdirectories
     */
    public function containsDirectories(ResourceType $resourceType, $path = '')
    {
        if (method_exists($this->adapter, 'containsDirectories')) {
            return $this->adapter->containsDirectories($this, $resourceType, $path, $this->acl);
        }

        $directoryPath = $this->buildPath($resourceType, $path);
        $contents = $this->listContents($directoryPath);

        foreach ($contents as $entry) {
            if ($entry['type'] === 'dir' &&
                !$this->isHiddenFolder($entry['basename']) &&
                $this->acl->isAllowed($resourceType->getName(), Path::combine($path, $entry['basename']), Permission::FOLDER_VIEW)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if file with given name is hidden
     *
     * @param string $fileName
     *
     * @return bool true if file is hidden
     */
    public function isHiddenFile($fileName)
    {
        $hideFilesRegex = $this->ckConfig->getHideFilesRegex();

        if ($hideFilesRegex) {
            return (bool) preg_match($hideFilesRegex, $fileName);
        }

        return false;
    }

    /**
     * Check if directory with given name is hidden
     *
     * @param string $folderName
     *
     * @return bool true if directory is hidden
     */
    public function isHiddenFolder($folderName)
    {
        $hideFoldersRegex = $this->ckConfig->getHideFoldersRegex();

        if ($hideFoldersRegex) {
            return (bool) preg_match($hideFoldersRegex, $folderName);
        }

        return false;
    }

    /**
     * Check if path is hidden
     *
     * @param string $path
     *
     * @return bool true if path is hidden
     */
    public function isHiddenPath($path)
    {
        $pathParts = explode('/', trim($path, '/'));
        if ($pathParts) {
            foreach ($pathParts as $part) {
                if ($this->isHiddenFolder($part)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Delete a directory
     *
     * @param string $dirname
     *
     * @return bool
     */
    public function deleteDir($dirname)
    {
        // For FTP first remove recursively all directory contents
        if ($this->adapter instanceof Ftp) {
            $this->deleteContents($dirname);
        }

        return parent::deleteDir($dirname);
    }

    /**
     * Delete all contents in given directory
     *
     * @param string $dirname
     */
    public function deleteContents($dirname)
    {
        $contents = $this->listContents($dirname);

        foreach ($contents as $entry) {
            if ($entry['type'] === 'dir') {
                $this->deleteContents($entry['path']);
                $this->deleteDir($entry['path']);
            } else {
                $this->delete($entry['path']);
            }
        }
    }

    /**
     * Checks if backend contains a directory
     *
     * The Backend::has() method is not always reliable and may
     * work differently for various adapters. Checking for directory
     * should be done with this method.
     *
     * @param string $directoryPath
     *
     * @return bool
     */
    public function hasDirectory($directoryPath)
    {
        // Temp fix for #74
        if ($this->adapter instanceof AwsS3Adapter) {
            $errorReporting = error_reporting();
            error_reporting($errorReporting & ~E_NOTICE);
        }

        $pathParts = array_filter(explode('/', $directoryPath), 'strlen');
        $dirName = array_pop($pathParts);
        $contents = $this->listContents(implode('/', $pathParts));

        foreach ($contents as $c) {
            if (isset($c['type']) && isset($c['basename']) && $c['type'] === 'dir' && $c['basename'] === $dirName) {
                return true;
            }
        }
    }

    /**
     * Returns a direct url to a file
     *
     * @param string $path
     *
     * @return string|null direct url to a file or null if backend
     *                     doesn't support direct access
     */
    public function getFileUrl($path)
    {
        if (method_exists($this->adapter, 'getFileUrl')) {
            return $this->adapter->getFileUrl($path);
        }

        if (isset($this->backendConfig['baseUrl'])) {
            return Path::combine($this->backendConfig['baseUrl'], Utils::encodeURLParts($path));
        }

        return null;
    }

    /**
     * Returns the base url used to build direct url to files stored
     * in this backend
     *
     * @return string|null base url or null if base url for a backend
     *                     was not defined
     */
    public function getBaseUrl()
    {
        if (isset($this->backendConfig['baseUrl'])) {
            return $this->backendConfig['baseUrl'];
        }

        return null;
    }

    /**
     * Returns the root directory defined for backend
     *
     * @return string|null root directory or null if root directory
     *                     was not defined
     */
    public function getRootDirectory()
    {
        if (isset($this->backendConfig['root'])) {
            return $this->backendConfig['root'];
        }

        return null;
    }

    /**
     * Creates a stream for writing
     *
     * @param string $path file path
     *
     * @return resource|null a stream to a file or null if backend doesn't
     *                       support writing streams
     */
    public function createWriteStream($path)
    {
        if (method_exists($this->adapter, 'createWriteStream')) {
            return $this->adapter->createWriteStream($path);
        }

        return null;
    }
}
