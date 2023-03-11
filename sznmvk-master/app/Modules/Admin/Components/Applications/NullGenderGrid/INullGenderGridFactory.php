<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Applications\NullGenderGrid;

interface INullGenderGridFactory
{
    public function create(): NullGenderGrid;
}