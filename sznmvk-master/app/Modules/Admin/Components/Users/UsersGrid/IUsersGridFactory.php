<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Users\UsersGrid;

interface IUsersGridFactory
{
    public function create(): UsersGrid;
}