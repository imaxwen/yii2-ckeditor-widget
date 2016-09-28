<?php

namespace CKSource\CKFinder\Command;

use CKSource\CKFinder\Acl\Permission;
use CKSource\CKFinder\Exception\InvalidNameException;
use CKSource\CKFinder\Exception\InvalidRequestException;
use CKSource\CKFinder\Filesystem\File\File;
use CKSource\CKFinder\Filesystem\Folder\WorkingFolder;
use CKSource\CKFinder\Image;
use CKSource\CKFinder\Config;
use CKSource\CKFinder\ResizedImage\ResizedImageRepository;
use Symfony\Component\HttpFoundation\Request;

class ImageResize extends CommandAbstract
{
    protected $requires = array(Permission::FILE_VIEW, Permission::IMAGE_RESIZE);

    public function execute(Request $request, WorkingFolder $workingFolder, Config $config, ResizedImageRepository $resizedImageRepository)
    {
        $fileName = $request->get('fileName');

        if (null === $fileName || !File::isValidName($fileName, $config->get('disallowUnsafeCharacters'))) {
            throw new InvalidRequestException('Invalid file name');
        }

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!Image::isSupportedExtension($ext)) {
            throw new InvalidNameException('Invalid source file name');
        }

        list($requestedWidth, $requestedHeight) = Image::parseSize($request->get('size'));

        $resizedImage = $resizedImageRepository->getResizedImage(
            $workingFolder->getResourceType(),
            $workingFolder->getClientCurrentFolder(),
            $fileName,
            $requestedWidth,
            $requestedHeight
        );

        return array('url' => $resizedImage->getUrl());
    }
}
