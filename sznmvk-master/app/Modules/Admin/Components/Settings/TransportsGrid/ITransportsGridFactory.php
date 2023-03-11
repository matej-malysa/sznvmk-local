<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\TransportsGrid;

interface ITransportsGridFactory
{
    public function create(): TransportsGrid;
}