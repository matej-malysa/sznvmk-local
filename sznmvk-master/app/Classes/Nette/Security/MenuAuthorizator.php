<?php declare(strict_types=1);


namespace App\Classes\Nette\Security;

use Carrooi\Menu\IMenuItem;
use Carrooi\Menu\Security\IAuthorizator;
use Nette\Security\User;

class MenuAuthorizator implements IAuthorizator
{
    /**
    * @var User
    */
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
    * Is menu item allowed for user?
    *
    * Each menu item can have three data attributes:

    * data:
    *      roles: array|string
    *      resources: array|string
    *      privileges: array|string defauts to Authorizator::VIEW
    *
    * If an attribute is an array, there is an OR relationship between the items.
    * There is an AND relationship between the attributes.
    * For example:
    *
    * data:
    *      roles: [root, authenticated]
    *      resources: [page, settings]
    *      privileges: [view, modify]
    *
    * Is evaluated as:
    *
    * ( isInRole(root) || isInRole(authenticated) )
    * &&
    * ( isAllowed(page, view) || isAllowed(page, modify) || isAllowed(settings, view) || isAllowed(settings, modify) )
    *
    * @param IMenuItem $item
    * @return bool
    */
    public function isMenuItemAllowed(IMenuItem $item): bool
    {
        $roles = $item->getData('roles') ?? [];
        $resources = $item->getData('resources') ?? [];
        $privileges = $item->getData('privileges') ?? Authorizator::VIEW;

        $allowedResources = $resources === [];
        $allowedRoles = $roles === [];

        // Normalize config parameters (turn strings to arrays).
        foreach (['roles' => &$roles, 'resources' => &$resources, 'privileges' => &$privileges] as $name => &$config) {
            if (is_string($config)) {
                $config = [$config];
            } elseif (!is_array($config)) {
                throw new \InvalidArgumentException(sprintf('Data attribute `%s` of menu item `%s` has to be array|string, %s given', $name, $item->getRealTitle(), gettype($config)));
            }
        }

        // Check roles
        foreach ($roles as $role) {
            if ($this->user->isInRole((string) $role)) {
                $allowedRoles = true;
                break;
            }
        }

        // Check resources
        foreach ($resources as $resource) {
            foreach ($privileges as $privilege) {
                if ($this->user->isAllowed((string) $resource, $privilege)) {
                    $allowedResources = true;
                    break;
                }
            }
        }

        return $allowedResources && $allowedRoles;
    }
}
