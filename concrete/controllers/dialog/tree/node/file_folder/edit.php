<?php
namespace Concrete\Controller\Dialog\Tree\Node\FileFolder;

use Concrete\Core\Entity\File\StorageLocation\StorageLocation as StorageLocationEntity;
use Concrete\Core\File\StorageLocation\StorageLocationFactory;
use Concrete\Core\Permission\Checker as Permissions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class Edit extends Add
{
    protected $viewPath = '/dialogs/tree/node/file_folder/edit';
    protected $helpers = ['form', 'validation/token'];

    protected function canAccess()
    {
        $node = $this->getNode();
        $np = new Permissions($node);

        return $np->canEditTreeNode();
    }

    public function view()
    {
        $node = $this->getNode();
        $this->set('node', $node);
        $storageLocations = $this->app->make(StorageLocationFactory::class)->fetchList();
        $locations = [];
        foreach ($storageLocations as $location) {
            if ($location->isDefault()) {
                $locations[$location->getID()] = t('%s (default)', $location->getName());
            } else {
                $locations[$location->getID()] = $location->getName();
            }
        }
        $this->set('locations', $locations);
    }

    public function update_file_folder_node()
    {
        $token = $this->app->make('token');
        $error = $this->app->make('error');
        if (!$token->validate('update_file_folder_node')) {
            $error->add($token->getErrorMessage());
        }

        $folderName = $this->request->request->get('fileFolderName');
        if (!is_string($folderName) || trim($folderName) === '') {
            $error->add(t('Invalid folder name'));
        }

        $fslID = $this->request->request->get('fileFolderFileStorageLocation');
        if (!$fslID) {
            $error->add(t('Please select a storage location'));
        } else {
            $em = $this->app->make(EntityManagerInterface::class);
            $storageLocation = $em->find(StorageLocationEntity::class, (int) $fslID);
            if (!is_object($storageLocation)) {
                $error->add(t('Please select a valid storage location'));
            }
        }

        if (!$error->has()) {
            $node = $this->getNode();
            $node->setTreeNodeName($folderName);
            $node->setTreeNodeStorageLocation($fslID);
            $r = $node->getTreeNodeJSON();

            return new JsonResponse($r);
        } else {
            return new JsonResponse($error);
        }
    }
}
