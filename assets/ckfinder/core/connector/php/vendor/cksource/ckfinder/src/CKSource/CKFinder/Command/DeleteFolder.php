<?php

namespace CKSource\CKFinder\Command;

use CKSource\CKFinder\Acl\Permission;
use CKSource\CKFinder\Event\CKFinderEvent;
use CKSource\CKFinder\Event\DeleteFolderEvent;
use CKSource\CKFinder\Exception\AccessDeniedException;
use CKSource\CKFinder\Exception\InvalidRequestException;
use CKSource\CKFinder\Filesystem\Folder\WorkingFolder;
use Symfony\Component\EventDispatcher\EventDispatcher;

class DeleteFolder extends CommandAbstract
{
    protected $requires = array(Permission::FOLDER_DELETE);

    public function execute(WorkingFolder $workingFolder, EventDispatcher $dispatcher)
    {
        // The root folder cannot be deleted.
        if ($workingFolder->getClientCurrentFolder() === '/') {
            throw new InvalidRequestException('Cannot delete resource type root folder');
        }

        $deleteFolderEvent = new DeleteFolderEvent($this->app, $workingFolder);

        $dispatcher->dispatch(CKFinderEvent::DELETE_FOLDER, $deleteFolderEvent);

        $deleted = false;

        if (!$deleteFolderEvent->isPropagationStopped()) {
            $deleted = $workingFolder->delete();
        }

        if (!$deleted) {
            throw new AccessDeniedException();
        }

        return array('deleted' => (int) $deleted);
    }
}
