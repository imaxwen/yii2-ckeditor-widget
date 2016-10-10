<?php


namespace CKSource\CKFinder\Command;


use CKSource\CKFinder\Acl\Permission;
use CKSource\CKFinder\Filesystem\Folder\WorkingFolder;
use Symfony\Component\HttpFoundation\Request;

class CreateFolder extends CommandAbstract
{
    protected $requires = array(Permission::FOLDER_CREATE);

    public function execute(Request $request, WorkingFolder $workingFolder)
    {
        $newFolderName = $request->query->get('newFolderName', '');

        $workingFolder->createDir($newFolderName);

        return array('newFolder' => $newFolderName);
    }
}
