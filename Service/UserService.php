<?php
namespace SAMLBundle\Service;

use Pimcore\Model\User;
use Pimcore\Bundle\AdminBundle\Security\User\TokenStorageUserResolver;
use Pimcore\Tool\Authentication;

class UserService
{
    public function save(array $userData): User
    {
        $hash = Authentication::getPasswordHash($userData['username'], $userData['password']);
        $user = User::create([
            'firstname' => $userData['firstname'],
            'lastname' => $userData['lastname'],
            'email' => $userData['email'], 
            'parentId' => intval($userData['parentId']),
            'name' => trim($userData['username']),
            'password' => $hash,
            'active' => true
        ]);

        $rid = $userData['rid'];
        if (!$rid) {
            return $user;
        }
        
        $rObject = User::getById($rid);
        if(!$rObject) {
            return $user;
        }
        
        $user->setParentId($rObject->getParentId());
        if ($rObject->getClasses()) {
            $user->setClasses(implode(',', $rObject->getClasses()));
        }

        if ($rObject->getDocTypes()) {
            $user->setDocTypes(implode(',', $rObject->getDocTypes()));
        }

        $keys = ['asset', 'document', 'object'];
        foreach ($keys as $key) {
            $getter = 'getWorkspaces' . ucfirst($key);
            $setter = 'setWorkspaces' . ucfirst($key);
            $workspaces = $rObject->$getter();
            $clonedWorkspaces = [];
            if (is_array($workspaces)) {
                foreach ($workspaces as $workspace) {
                    $vars = get_object_vars($workspace);
                    if ($key == 'object') {
                        $workspaceClass = '\\Pimcore\\Model\\User\\Workspace\\DataObject';
                    } else {
                        $workspaceClass = '\\Pimcore\\Model\\User\\Workspace\\' . ucfirst($key);
                    }
                    $newWorkspace = new $workspaceClass();
                    foreach ($vars as $varKey => $varValue) {
                        $newWorkspace->$varKey = $varValue;
                    }
                    $newWorkspace->setUserId($user->getId());
                    $clonedWorkspaces[] = $newWorkspace;
                }
            }   

            $user->$setter($clonedWorkspaces);
        }

        $user->setPermissions($rObject->getPermissions());

        $user->setAdmin(false);
        $user->setAdmin(false);
        if ($this->getAdminUser()->isAdmin()) {
            $user->setAdmin($rObject->getAdmin());
        }
        $user->setActive($rObject->getActive());
        $user->setRoles($rObject->getRoles());
        $user->setWelcomeScreen($rObject->getWelcomescreen());
        $user->setMemorizeTabs($rObject->getMemorizeTabs());
        $user->setCloseWarning($rObject->getCloseWarning());
        $user->save();

        return $user;
    }
    
    protected function getAdminUser()
    {
        $resolver = \Pimcore::getContainer()->get(TokenStorageUserResolver::class);
        return $resolver->getUser();
    }
    
    public function getUserByUsername(string $username)
    {
        return User::getByName($username);
    }
}