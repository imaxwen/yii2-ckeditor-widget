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

namespace CKSource\CKFinder\ResizedImage;

use CKSource\CKFinder\Exception\FileNotFoundException;
use CKSource\CKFinder\Filesystem\Path;
use CKSource\CKFinder\Image;
use CKSource\CKFinder\ResourceType\ResourceType;

/**
 * Resized image class
 *
 * Class representing an image that was resized according to given dimensions
 *
 * @copyright 2015 CKSource - Frederico Knabben
 */
class ResizedImage extends ResizedImageAbstract
{
    const DIR = '__thumbs';

    /**
     * @var ResizedImageRepository
     */
    protected $resizedImageRepository;

    /**
     * @var Image $image
     */
    protected $image;

    /**
     * @var int $width
     */
    protected $width;

    /**
     * @var int $height
     */
    protected $height;

    /**
     * @var bool $requestedSizeIsValid
     */
    protected $requestedSizeIsValid = true;

    /**
     * Full source file path
     *
     * @var string $sourceFileDir
     */
    protected $sourceFileDir;

    /**
     * @param ResizedImageRepository $resizedImageRepository
     * @param ResourceType           $sourceFileResourceType
     * @param string                 $sourceFileDir
     * @param string                 $sourceFileName
     * @param int                    $requestedWidth
     * @param int                    $requestedHeight
     *
     * @throws \Exception if source image is invalid
     */
    public function __construct(ResizedImageRepository $resizedImageRepository, ResourceType $sourceFileResourceType, $sourceFileDir, $sourceFileName, $requestedWidth, $requestedHeight)
    {
        parent::__construct($sourceFileResourceType, $sourceFileDir, $sourceFileName, $requestedWidth, $requestedHeight);

        $this->resizedImageRepository = $resizedImageRepository;

        $backend = $this->backend = $sourceFileResourceType->getBackend();

        // Check if there's info about source image in cache
        $app = $this->resizedImageRepository->getContainer();

        $cacheKey = Path::combine($sourceFileResourceType->getName(), $sourceFileDir, $sourceFileName);

        $cachedInfo = $app['cache']->get($cacheKey);

        // No info cached, get original image
        if (null === $cachedInfo || !isset($cachedInfo['width']) || !isset($cachedInfo['height'])) {
            $sourceFilePath = Path::combine($sourceFileResourceType->getDirectory(), $sourceFileDir, $sourceFileName);

            if ($backend->isHiddenFile($sourceFileName) || !$backend->has($sourceFilePath)) {
                throw new FileNotFoundException('ResizedImage::create(): Source file not found');
            }

            $originalImage = $this->image = Image::create($backend->read($sourceFilePath));

            $app['cache']->set($cacheKey, $originalImage->getInfo());

            $originalImageWidth = $originalImage->getWidth();
            $originalImageHeight = $originalImage->getHeight();
        } else {
            $originalImageWidth = $cachedInfo['width'];
            $originalImageHeight = $cachedInfo['height'];
        }

        $targetSize = Image::calculateAspectRatio($requestedWidth, $requestedHeight, $originalImageWidth, $originalImageHeight);

        if ($targetSize['width'] >= $originalImageWidth || $targetSize['height'] >= $originalImageHeight) {
            $this->width = $originalImageWidth;
            $this->height = $originalImageHeight;
            $this->requestedSizeIsValid = false;
        } else {
            $this->width = $targetSize['width'];
            $this->height = $targetSize['height'];
        }


        $this->resizedImageFileName = static::createFilename($sourceFileName, $this->width, $this->height);
    }

    public static function createFilename($fileName, $width, $height)
    {
        $pathInfo = pathinfo($fileName);

        return sprintf("%s__%dx%d%s", $pathInfo['filename'], $width, $height, isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '');
    }

    public static function getSizeFromFilename($resizedImageFileName)
    {
        $pathInfo = pathinfo($resizedImageFileName);

        preg_match('/^.*__(\d+)x(\d+)$/', $pathInfo['filename'], $matches);

        if (count($matches) === 3) {
            return array(
                'width'  => (int) $matches[1],
                'height' => (int) $matches[2]
            );
        }

        return null;
    }

    /**
     * Returns directory of the resized image
     *
     * @return string
     */
    public function getDirectory()
    {
        return Path::combine($this->sourceFileResourceType->getDirectory(),
            $this->sourceFileDir,
            ResizedImage::DIR,
            $this->sourceFileName
        );
    }

    /**
     * Creates resized image
     */
    public function create()
    {
        if (null === $this->image) {
            $sourceFilePath = Path::combine($this->sourceFileResourceType->getDirectory(), $this->sourceFileDir, $this->sourceFileName);

            if ($this->backend->isHiddenFile($this->sourceFileName) || !$this->backend->has($sourceFilePath)) {
                throw new FileNotFoundException('ResizedImage::create(): Source file not found');
            }

            $this->image = Image::create($this->backend->read($sourceFilePath));
        }

        $this->image->resize($this->width, $this->height);
        $this->resizedImageData = $this->image->getData();
        $this->resizedImageSize = $this->image->getDataSize();
        $this->resizedImageMimeType = $this->image->getMimeType();
    }

    /**
     * Returns direct url to resized image
     *
     * @return string
     */
    public function getUrl()
    {
        $backend = $this->sourceFileResourceType->getBackend();

        /**
         * In case if requested size is bigger than size of the original image
         * the resized version was not created.
         * This is a fallback that returns URL to the original image.
         */
        if (!$this->requestedSizeIsValid()) {
            $sourceFilePath = Path::combine($this->sourceFileResourceType->getDirectory(), $this->sourceFileDir, $this->sourceFileName);

            return $backend->getFileUrl($sourceFilePath);
        }

        return $backend->getFileUrl($this->getFilePath());
    }

    /**
     * Checks if size requested for resized image is valid
     *
     * @return bool true if requested size is valid
     */
    public function requestedSizeIsValid()
    {
        return $this->requestedSizeIsValid;
    }
}
