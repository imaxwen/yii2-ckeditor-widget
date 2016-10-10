<?php


namespace CKSource\CKFinder\Command;


use CKSource\CKFinder\Acl\Acl;
use CKSource\CKFinder\Acl\Permission;
use CKSource\CKFinder\Error;
use CKSource\CKFinder\Event\CKFinderEvent;
use CKSource\CKFinder\Event\DeleteFileEvent;
use CKSource\CKFinder\Exception\InvalidRequestException;
use CKSource\CKFinder\Exception\UnauthorizedException;
use CKSource\CKFinder\Filesystem\File\DeletedFile;
use CKSource\CKFinder\ResourceType\ResourceTypeFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class DeleteFiles extends CommandAbstract
{
    protected $requires = array(
        Permission::FILE_DELETE
    );

    public function execute(Request $request, ResourceTypeFactory $resourceTypeFactory, Acl $acl, EventDispatcher $dispatcher)
    {
        $deletedFiles = (array) $request->get('files');

        $deleted = 0;

        $errors = array();

        foreach ($deletedFiles as $arr) {
            if (!isset($arr['name'], $arr['type'], $arr['folder'])) {
                throw new InvalidRequestException();
            }

            if (empty($arr['name'])) {
                continue;
            }

            $name   = $arr['name'];
            $type   = $arr['type'];
            $folder = $arr['folder'];

            $resourceType = $resourceTypeFactory->getResourceType($type);

            $deletedFile = new DeletedFile($name, $folder, $resourceType, $this->app);

            if (!$acl->isAllowed($type, $folder, Permission::FILE_DELETE)) {
                throw new UnauthorizedException();
            }

            if ($deletedFile->isValid()) {
                $deleteFileEvent = new DeleteFileEvent($this->app, $deletedFile);
                $dispatcher->dispatch(CKFinderEvent::DELETE_FILE, $deleteFileEvent);

                if (!$deleteFileEvent->isPropagationStopped()) {
                    if ($deletedFile->doDelete()) {
                        $deleted++;
                    }
                }
            }

            $errors = array_merge($errors, $deletedFile->getErrors());
        }

        $data = array('deleted' => $deleted);

        if (!empty($errors)) {
            $data['error'] = array(
                'number' => Error::DELETE_FAILED,
                'errors' => $errors
            );
        }

        return $data;
    }
}
