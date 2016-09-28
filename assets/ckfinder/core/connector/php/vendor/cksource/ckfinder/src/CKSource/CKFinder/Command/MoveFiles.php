<?php


namespace CKSource\CKFinder\Command;


use CKSource\CKFinder\Acl\Acl;
use CKSource\CKFinder\Acl\Permission;
use CKSource\CKFinder\Error;
use CKSource\CKFinder\Event\CKFinderEvent;
use CKSource\CKFinder\Event\MoveFileEvent;
use CKSource\CKFinder\Exception\InvalidRequestException;
use CKSource\CKFinder\Exception\UnauthorizedException;
use CKSource\CKFinder\Filesystem\File\MovedFile;
use CKSource\CKFinder\ResourceType\ResourceTypeFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class MoveFiles extends CommandAbstract
{
    protected $requires = array(
        Permission::FILE_RENAME,
        Permission::FILE_UPLOAD,
        Permission::FILE_DELETE
    );

    public function execute(Request $request, ResourceTypeFactory $resourceTypeFactory, Acl $acl, EventDispatcher $dispatcher)
    {
        $movedFiles = (array) $request->get('files');

        $moved = 0;

        $errors = array();

        foreach ($movedFiles as $arr) {
            if (!isset($arr['name'], $arr['type'], $arr['folder'])) {
                throw new InvalidRequestException('Invalid request');
            }

            if (empty($arr['name'])) {
                continue;
            }

            $name   = $arr['name'];
            $type   = $arr['type'];
            $folder = $arr['folder'];

            $resourceType = $resourceTypeFactory->getResourceType($type);

            $movedFile = new MovedFile($name, $folder, $resourceType, $this->app);

            $options = isset($arr['options']) ? $arr['options'] : '';

            if (!$acl->isAllowed($type, $folder, Permission::FILE_VIEW | Permission::FILE_DELETE)) {
                throw new UnauthorizedException('Unauthorized');
            }


            if ($movedFile->isValid()) {
                $moveFileEvent = new MoveFileEvent($this->app, $movedFile);
                $dispatcher->dispatch(CKFinderEvent::MOVE_FILE, $moveFileEvent);

                if (!$moveFileEvent->isPropagationStopped()) {
                    if ($movedFile->doMove($options)) {
                        $moved++;
                    }
                }
            }

            $errors = array_merge($errors, $movedFile->getErrors());
        }

        $data = array('moved' => $moved);

        if (!empty($errors)) {
            $data['error'] = array(
                'number' => Error::MOVE_FAILED,
                'errors' => $errors
            );
        }

        return $data;
    }
}
