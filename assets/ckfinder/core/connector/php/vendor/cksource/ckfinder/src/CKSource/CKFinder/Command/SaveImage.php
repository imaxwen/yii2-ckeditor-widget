<?php

namespace CKSource\CKFinder\Command;


use CKSource\CKFinder\Cache\CacheManager;
use CKSource\CKFinder\Event\CKFinderEvent;
use CKSource\CKFinder\Event\EditFileEvent;
use CKSource\CKFinder\Exception\CKFinderException;
use CKSource\CKFinder\Exception\InvalidExtensionException;
use CKSource\CKFinder\Exception\InvalidUploadException;
use CKSource\CKFinder\Filesystem\File\EditedFile;
use CKSource\CKFinder\Filesystem\Path;
use CKSource\CKFinder\Image;
use CKSource\CKFinder\Filesystem\Folder\WorkingFolder;
use CKSource\CKFinder\ResizedImage\ResizedImageRepository;
use CKSource\CKFinder\Thumbnail\ThumbnailRepository;
use CKSource\CKFinder\Utils;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class SaveImage extends CommandAbstract
{
    public function execute(Request $request, WorkingFolder $workingFolder, EventDispatcher $dispatcher, CacheManager $cache, ResizedImageRepository $resizedImageRepository, ThumbnailRepository $thumbnailRepository)
    {
        $fileName = $request->query->get('fileName');

        $editedFile = new EditedFile($fileName, $this->app);

        $saveAsNew = false;

        if (!$editedFile->exists()) {
            $saveAsNew = true;
            $editedFile->saveAsNew(true);
        }

        if (!$editedFile->isValid()) {
            throw new InvalidUploadException('Invalid file provided');
        }

        if (!Image::isSupportedExtension($editedFile->getExtension())) {
            throw new InvalidExtensionException('Unsupported image type or not image file');
        }

        $imageFormat = Image::mimeTypeFromExtension($editedFile->getExtension());

        $uploadedData = $request->get('content');

        if (null === $uploadedData || strpos($uploadedData, 'data:image/png;base64,') !== 0) {
            throw new InvalidUploadException('Invalid upload. Expected base64 encoded PNG image.');
        }

        $data = explode(',', $uploadedData);
        $data = isset($data[1]) ? base64_decode($data[1]) : false;

        if (!$data) {
            throw new InvalidUploadException();
        }

        $uploadedImage = Image::create($data);

        $newContents = $uploadedImage->getData($imageFormat);

        $editFileEvent = new EditFileEvent($this->app, $editedFile, $newContents);

        $cache->set(
            Path::combine(
                $workingFolder->getResourceType()->getName(),
                $workingFolder->getClientCurrentFolder(),
                $fileName),
            $uploadedImage->getInfo()
        );

        $dispatcher->dispatch(CKFinderEvent::SAVE_IMAGE, $editFileEvent);

        $saved = false;

        if (!$editFileEvent->isPropagationStopped()) {
            $saved = $editedFile->setContents($editFileEvent->getNewContents());

            //Remove thumbnails and resized images in case if file is overwritten
            if (!$saveAsNew && $saved) {
                $resourceType = $workingFolder->getResourceType();
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
