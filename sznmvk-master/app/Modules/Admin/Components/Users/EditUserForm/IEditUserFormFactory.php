<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Users\EditUserForm;

interface IEditUserFormFactory
{
    public function create(int $id): EditUserForm;
}