<?php

namespace CKSource\CKFinder\Command;

use CKSource\CKFinder\Acl\Permission;
use CKSource\CKFinder\Exception\InvalidRequestException;
use CKSource\CKFinder\Filesystem\Folder\WorkingFolder;
use Symfony\Component\HttpFoundation\Request;

class RenameFolder extends CommandAbstract
{
    protected $requires = array(Permission::FOLDER_RENAME);

    public function execute(Request $request, WorkingFolder $workingFolder)
    {
        // The root folder cannot be renamed.
        if ($workingFolder->getClientCurrentFolder() === '/') {
            throw new InvalidRequestException('Cannot rename resource type root folder');
        }

        $newFolderName = $request->query->get('newFolderName');

        return $workingFolder->rename($newFolderName);
    }
}


