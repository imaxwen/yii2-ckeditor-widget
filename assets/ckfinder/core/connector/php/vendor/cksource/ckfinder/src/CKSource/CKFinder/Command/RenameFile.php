<?php

namespace CKSource\CKFinder\Command;

use CKSource\CKFinder\Acl\Permission;
use CKSource\CKFinder\Event\CKFinderEvent;
use CKSource\CKFinder\Event\RenameFileEvent;
use CKSource\CKFinder\Exception\AccessDeniedException;
use CKSource\CKFinder\Exception\InvalidNameException;
use CKSource\CKFinder\Filesystem\File\RenamedFile;
use CKSource\CKFinder\Filesystem\Folder\WorkingFolder;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class RenameFile extends CommandAbstract
{
    protected $requires = array(Permission::FILE_RENAME);

    public function execute(Request $request, WorkingFolder $workingFolder, EventDispatcher $dispatcher)
    {
        $fileName = $request->get('fileName');
        $newFileName = $request->get('newFileName');

        if (null === $fileName || null === $newFileName) {
            throw new InvalidNameException('Invalid file name');
        }

        $renamedFile = new RenamedFile(
            $newFileName,
            $fileName,
            $workingFolder->getClientCurrentFolder(),
            $workingFolder->getResourceType(),
            $this->app
        );

        if ($renamedFile->isValid()) {
            $renamedFileEvent = new RenameFileEvent($this->app, $renamedFile);

            $dispatcher->dispatch(CKFinderEvent::RENAME_FILE, $renamedFileEvent);

            if (!$renamedFileEvent->isPropagationStopped()) {
                if (!$renamedFile->doRename()) {
                    throw new AccessDeniedException();
                }
            }
        }


        return array(
            'name'    => $fileName,
            'newName' => $newFileName
        );
    }
}
