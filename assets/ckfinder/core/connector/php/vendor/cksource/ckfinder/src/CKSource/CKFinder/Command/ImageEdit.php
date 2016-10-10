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

namespace CKSource\CKFinder\Command;

use CKSource\CKFinder\Acl\Acl;
use CKSource\CKFinder\Acl\Permission;
use CKSource\CKFinder\Event\CKFinderEvent;
use CKSource\CKFinder\Event\EditFileEvent;
use CKSource\CKFinder\Exception\InvalidExtensionException;
use CKSource\CKFinder\Exception\InvalidRequestException;
use CKSource\CKFinder\Exception\UnauthorizedException;
use CKSource\CKFinder\Filesystem\File\EditedFile;
use CKSource\CKFinder\Filesystem\Folder\WorkingFolder;
use CKSource\CKFinder\Image;
use CKSource\CKFinder\ResizedImage\ResizedImageRepository;
use CKSource\CKFinder\Thumbnail\ThumbnailRepository;
use CKSource\CKFinder\Utils;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * ImageEdit command class
 *
 * This command performs basic image modifications:
 * - crop
 * - rotate
 * - resize
 *
 * @copyright 2015 CKSource - Frederico Knabben
 */
class ImageEdit extends CommandAbstract
{
    const OPERATION_CROP   = 'crop';
    const OPERATION_ROTATE = 'rotate';
    const OPERATION_RESIZE = 'resize';

    protected $requires = array(Permission::FILE_VIEW);

    /**
     * @param Request         $request
     * @param WorkingFolder   $workingFolder
     * @param EventDispatcher $dispatcher
     * @param Acl             $acl
     *
     * @return array
     *
     * @throws \Exception
     */
    public function execute(Request $request, WorkingFolder $workingFolder, EventDispatcher $dispatcher, Acl $acl, ResizedImageRepository $resizedImageRepository, ThumbnailRepository $thumbnailRepository)
    {
        $fileName = $request->get('fileName');
        $newFileName = $request->get('newFileName');

        $editedFile = new EditedFile($fileName, $this->app, $newFileName);
        $editedFile->isValid();

        $resourceType = $workingFolder->getResourceType();

        if ($newFileName) {
            $resourceTypeName = $resourceType->getName();
            $path = $workingFolder->getClientCurrentFolder();

            if (!$acl->isAllowed($resourceTypeName, $path, Permission::FILE_UPLOAD)) {
                throw new UnauthorizedException(sprintf('Unauthorized: no FILE_UPLOAD permission in %s:%s', $resourceTypeName, $path));
            }
        }

        if (!Image::isSupportedExtension($editedFile->getExtension())) {
            throw new InvalidExtensionException('Unsupported image type or not image file');
        }

        $image = Image::create($editedFile->getContents());

        $actions = (array) $request->get('actions');

        foreach ($actions as $actionInfo) {
            if (!isset($actionInfo['action'])) {
                throw new InvalidRequestException('ImageEdit: action name missing');
            }

            switch ($actionInfo['action']) {
                case self::OPERATION_CROP:
                    if (!Utils::arrayContainsKeys($actionInfo, array('x', 'y', 'width', 'height'))) {
                        throw new InvalidRequestException();
                    }
                    $x = $actionInfo['x'];
                    $y = $actionInfo['y'];
                    $width = $actionInfo['width'];
                    $height = $actionInfo['height'];
                    $image->crop($x, $y, $width, $height);
                    break;

                case self::OPERATION_ROTATE:
                    if (!isset($actionInfo['angle'])) {
                        throw new InvalidRequestException();
                    }
                    $degrees = $actionInfo['angle'];
                    $bgcolor = isset($actionInfo['bgcolor']) ? $actionInfo['bgcolor'] : 0;
                    $image->rotate($degrees, $bgcolor);
                    break;

                case self::OPERATION_RESIZE:
                    if (!Utils::arrayContainsKeys($actionInfo, array('width', 'height'))) {
                        throw new InvalidRequestException();
                    }
                    $width = $actionInfo['width'];
                    $height = $actionInfo['height'];
                    $image->resize($width, $height);
                    break;
            }
        }

        $newContents = $image->getData();

        $editFileEvent = new EditFileEvent($this->app, $editedFile, $newContents);

        $dispatcher->dispatch(CKFinderEvent::EDIT_IMAGE, $editFileEvent);

        $saved = false;

        if (!$editFileEvent->isPropagationStopped()) {
            $saved = $editedFile->setContents($editFileEvent->getNewContents());

            //Remove thumbnails and resized images in case if file is overwritten
            if ($newFileName === null && $saved) {
                $thumbnailRepository->deleteThumbnails($resourceType, $workingFolder->getClientCurrentFolder(), $fileName);
                $resizedImageRepository->deleteResizedImages($resourceType, $workingFolder->getClientCurrentFolder(), $fileName);
            }
        }

        return array(
            'saved' => (int) $saved,
            'date'  => Utils::formatDate(time())
        );
    }
}
