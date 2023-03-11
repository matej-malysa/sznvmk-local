<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Users\AddUserForm;

interface IAddUserFormFactory
{
    public function create(): AddUserForm;
}