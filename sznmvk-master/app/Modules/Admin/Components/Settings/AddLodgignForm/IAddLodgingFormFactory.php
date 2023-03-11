<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\AddLodgingForm;

interface IAddLodgingFormFactory
{
    public function create(): AddLodgingForm;
}