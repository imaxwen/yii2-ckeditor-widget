<?php

namespace CKSource\CKFinder\Command;


use CKSource\CKFinder\Acl\Permission;
use CKSource\CKFinder\Config;
use CKSource\CKFinder\Exception\FileNotFoundException;
use CKSource\CKFinder\Exception\InvalidRequestException;
use CKSource\CKFinder\Filesystem\File\File;
use CKSource\CKFinder\Filesystem\Folder\WorkingFolder;
use CKSource\CKFinder\Filesystem\Path;
use CKSource\CKFinder\ResizedImage\ResizedImage;
use Symfony\Component\HttpFoundation\Request;

class GetFileUrl extends CommandAbstract
{
    protected $requires = array(Permission::FILE_VIEW);

    public function execute(WorkingFolder $workingFolder, Request $request, Config $config)
    {
        $fileName = $request->get('fileName');
        $thumbnail = $request->get('thumbnail');

        $fileNames = (array) $request->get('fileNames');

        if (!empty($fileNames)) {
            $urls = array();

            foreach ($fileNames as $fileName) {
                if (!File::isValidName($fileName, $config->get('disallowUnsafeCharacters'))) {
                    throw new InvalidRequestException(sprintf('Invalid file name: %s', $fileName));
                }

                $urls[$fileName] = $workingFolder->getFileUrl($fileName);

            }

            return array('urls' => $urls);
        }

        if (!File::isValidName($fileName, $config->get('disallowUnsafeCharacters')) ||
            ($thumbnail && !File::isValidName($thumbnail, $config->get('disallowUnsafeCharacters')))) {
            throw new InvalidRequestException('Invalid file name');
        }

        if (!$workingFolder->containsFile($fileName)) {
            throw new FileNotFoundException();
        }

        return array(
            'url' => $workingFolder->getFileUrl(
                $thumbnail
                ? Path::combine(ResizedImage::DIR, $fileName, $thumbnail)
                : $fileName
            )
        );
    }
}
