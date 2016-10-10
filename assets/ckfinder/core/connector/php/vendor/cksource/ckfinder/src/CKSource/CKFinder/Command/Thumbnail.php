<?php

namespace CKSource\CKFinder\Command;

use CKSource\CKFinder\Acl\Permission;
use CKSource\CKFinder\Error;
use CKSource\CKFinder\Config;
use CKSource\CKFinder\Exception\CKFinderException;
use CKSource\CKFinder\Exception\InvalidNameException;
use CKSource\CKFinder\Exception\InvalidRequestException;
use CKSource\CKFinder\Filesystem\File\File;
use CKSource\CKFinder\Filesystem\Folder\WorkingFolder;
use CKSource\CKFinder\Image;
use CKSource\CKFinder\Thumbnail\ThumbnailRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Thumbnail extends CommandAbstract
{
    protected $requires = array(Permission::FILE_VIEW);

    public function execute(Request $request, WorkingFolder $workingFolder, Config $config, ThumbnailRepository $thumbnailRepository)
    {
        if (!$config->get('thumbnails.enabled')) {
            throw new CKFinderException('Thumbnails feature is disabled', Error::THUMBNAILS_DISABLED);
        }

        $fileName = $request->get('fileName');

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!Image::isSupportedExtension($ext, $thumbnailRepository->isBitmapSupportEnabled())) {
            throw new InvalidNameException('Invalid source file name');
        }

        if (null === $fileName || !File::isValidName($fileName, $config->get('disallowUnsafeCharacters'))) {
            throw new InvalidRequestException('Invalid file name');
        }

        list($requestedWidth, $requestedHeight) = Image::parseSize($request->get('size'));

        $thumbnail = $thumbnailRepository->getThumbnail($workingFolder->getResourceType(),
            $workingFolder->getClientCurrentFolder(), $fileName, $requestedWidth, $requestedHeight);

        /**
         * This was added on purpose to reset any Cache-Control headers set
         * for example by session_start(). Symfony Session has a workaround,
         * but but we can't rely on this as application may not use Symfony
         * components to handle sessions.
         */
        header('Cache-Control:');

        $response = new Response();
        $response->setPublic();
        $response->setEtag(dechex($thumbnail->getTimestamp()) . "-" . dechex($thumbnail->getSize()));

        $lastModificationDate = new \DateTime();
        $lastModificationDate->setTimestamp($thumbnail->getTimestamp());

        $response->setLastModified($lastModificationDate);

        if ($response->isNotModified($request)) {
            return $response;
        }

        $thumbnailsCacheExpires = (int) $config->get('cache.thumbnails');

        if ($thumbnailsCacheExpires > 0) {
            $response->setMaxAge($thumbnailsCacheExpires);

            $expireTime = new \DateTime();
            $expireTime->modify('+' . $thumbnailsCacheExpires . 'seconds');
            $response->setExpires($expireTime);
        }


        $response->headers->set('Content-Type', $thumbnail->getMimeType() . '; name="' . $thumbnail->getFileName() . '"');
        $response->setContent($thumbnail->getImageData());

        return $response;
    }
}
