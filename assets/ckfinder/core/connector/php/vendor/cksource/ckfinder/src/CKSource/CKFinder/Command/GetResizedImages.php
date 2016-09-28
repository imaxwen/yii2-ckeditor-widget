<?php

namespace CKSource\CKFinder\Command;


use CKSource\CKFinder\Acl\Permission;
use CKSource\CKFinder\Cache\CacheManager;
use CKSource\CKFinder\Exception\InvalidRequestException;
use CKSource\CKFinder\Filesystem\Folder\WorkingFolder;
use CKSource\CKFinder\Filesystem\Path;
use CKSource\CKFinder\Image;
use CKSource\CKFinder\ResizedImage\ResizedImageRepository;
use CKSource\CKFinder\Config;
use Symfony\Component\HttpFoundation\Request;

class GetResizedImages extends CommandAbstract
{
    protected $requires = array(Permission::FILE_VIEW);

    public function execute(Request $request, WorkingFolder $workingFolder, ResizedImageRepository $resizedImageRepository, Config $config, CacheManager $cache)
    {
        $fileName = $request->get('fileName');
        $sizes = $request->get('sizes');

        $ext = pathinfo($fileName, PATHINFO_EXTENSION);

        if (!Image::isSupportedExtension($ext)) {
            throw new InvalidRequestException('Invalid file extension');
        }

        if ($sizes) {
            $sizes = explode(',', $sizes);
            if (array_diff($sizes, array_keys($config->get('images.sizes')))) {
                throw new InvalidRequestException(sprintf('Invalid size requested (%s)', $request->get('sizes')));
            }
        }

        $data = array();

        $cachedInfo = $cache->get(
            Path::combine(
                $workingFolder->getResourceType()->getName(),
                $workingFolder->getClientCurrentFolder(),
                $fileName
            )
        );

        if ($cachedInfo && isset($cachedInfo['width']) && isset($cachedInfo['height'])) {
            $data['originalSize'] = sprintf("%dx%d", $cachedInfo['width'], $cachedInfo['height']);
        }

        $resizedImages = $resizedImageRepository->getResizedImagesList(
            $workingFolder->getResourceType(),
            $workingFolder->getClientCurrentFolder(),
            $fileName,
            $sizes ?: array()
        );

        $data['resized'] = $resizedImages;

        return $data;
    }
}
