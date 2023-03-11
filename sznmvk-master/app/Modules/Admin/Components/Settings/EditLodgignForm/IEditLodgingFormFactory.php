<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\EditLodgingForm;

interface IEditLodgingFormFactory
{
    public function create(int $lodgingId): EditLodgingForm;
}