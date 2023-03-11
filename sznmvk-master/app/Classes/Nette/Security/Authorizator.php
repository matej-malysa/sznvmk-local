<?php

namespace App\Classes\Nette\Security;

class Authorizator implements \Nette\Security\Authorizator
{
    const VIEW = 'view';
    const MODIFY = 'modify';

    const RESOURCE_INSTRUCTORS = 'instructors';
    const RESOURCE_SETTINGS = 'settings';
    const RESOURCE_APPLICATIONS = 'applications';
    const RESOURCE_PARTICIPANTS = "participants";
    const RESOURCE_PAYMENTS = 'payments';
    const RESOURCE_CAMP_DATA = 'camp_data';

    const ROLE_ADMIN = 1;
    const ROLE_LEADER = 2;
    const ROLE_INSTRUCTOR = 3;
    const ROLE_USER = 4;

    public function isAllowed($role, $resource, $operation): bool
    {
        if (($role == self::ROLE_ADMIN) || ($role == self::ROLE_LEADER)) {
            return true;
        }
        if ($role == self::ROLE_INSTRUCTOR && ($resource == self::RESOURCE_PARTICIPANTS || $resource == self::RESOURCE_APPLICATIONS || $resource == self::RESOURCE_CAMP_DATA)) {
            return true;
        }

        return false;
    }
}
