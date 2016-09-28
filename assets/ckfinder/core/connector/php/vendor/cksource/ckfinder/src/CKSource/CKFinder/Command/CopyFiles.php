<?php


namespace CKSource\CKFinder\Command;


use CKSource\CKFinder\Acl\Acl;
use CKSource\CKFinder\Acl\Permission;
use CKSource\CKFinder\Error;
use CKSource\CKFinder\Event\CKFinderEvent;
use CKSource\CKFinder\Event\CopyFileEvent;
use CKSource\CKFinder\Exception\InvalidRequestException;
use CKSource\CKFinder\Exception\UnauthorizedException;
use CKSource\CKFinder\Filesystem\File\CopiedFile;
use CKSource\CKFinder\ResourceType\ResourceTypeFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class CopyFiles extends CommandAbstract
{
    protected $requires = array(
        Permission::FILE_RENAME,
        Permission::FILE_UPLOAD,
        Permission::FILE_DELETE
    );

    public function execute(Request $request, ResourceTypeFactory $resourceTypeFactory, Acl $acl, EventDispatcher $dispatcher)
    {
        $copiedFiles = (array) $request->get('files');

        $copied = 0;

        $errors = array();

        foreach ($copiedFiles as $arr) {
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

            $copiedFile = new CopiedFile($name, $folder, $resourceType, $this->app);

            $options = isset($arr['options']) ? $arr['options'] : '';

            if (!$acl->isAllowed($type, $folder, Permission::FILE_VIEW)) {
                throw new UnauthorizedException();
            }

            if ($copiedFile->isValid()) {
                $copyFileEvent = new CopyFileEvent($this->app, $copiedFile);
                $dispatcher->dispatch(CKFinderEvent::COPY_FILE, $copyFileEvent);

                if (!$copyFileEvent->isPropagationStopped()) {
                    if ($copiedFile->doCopy($options)) {
                        $copied++;
                    }
                }
            }

            $errors = array_merge($errors, $copiedFile->getErrors());
        }

        $data = array('copied' => $copied);

        if (!empty($errors)) {
            $data['error'] = array(
                'number' => Error::COPY_FAILED,
                'errors' => $errors
            );
        }

        return $data;
    }
}
