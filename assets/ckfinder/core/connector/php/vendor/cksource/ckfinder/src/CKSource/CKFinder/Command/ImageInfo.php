<?php

namespace CKSource\CKFinder\Command;


use CKSource\CKFinder\Acl\Permission;
use CKSource\CKFinder\Cache\CacheManager;
use CKSource\CKFinder\Config;
use CKSource\CKFinder\Exception\InvalidNameException;
use CKSource\CKFinder\Exception\InvalidRequestException;
use CKSource\CKFinder\Filesystem\File\DownloadedFile;
use CKSource\CKFinder\Filesystem\File\File;
use CKSource\CKFinder\Filesystem\Folder\WorkingFolder;
use CKSource\CKFinder\Filesystem\Path;
use CKSource\CKFinder\Image;
use Symfony\Component\HttpFoundation\Request;

class ImageInfo extends CommandAbstract
{
    protected $requires = array(
        Permission::FILE_VIEW
    );

    public function execute(Request $request, WorkingFolder $workingFolder, Config $config, CacheManager $cache)
    {
        $fileName = $request->get('fileName');

        if (null === $fileName || !File::isValidName($fileName, $config->get('disallowUnsafeCharacters'))) {
            throw new InvalidRequestException('Invalid file name');
        }

        if (!Image::isSupportedExtension(pathinfo($fileName, PATHINFO_EXTENSION))) {
            throw new InvalidNameException('Invalid source file name');
        }

        $cachePath = Path::combine(
            $workingFolder->getResourceType()->getName(),
            $workingFolder->getClientCurrentFolder(),
            $fileName
        );

        $imageInfo = array();

        $cachedInfo = $cache->get($cachePath);

        if ($cachedInfo && isset($cachedInfo['width']) && isset($cachedInfo['height'])) {
            $imageInfo = $cachedInfo;
        } else {
            $file = new DownloadedFile($fileName, $this->app);

            if ($file->isValid()) {
                $image = Image::create($file->getContents());
                $imageInfo = $image->getInfo();
                $cache->set($cachePath, $imageInfo);
            }
        }

        return $imageInfo;
    }
}
