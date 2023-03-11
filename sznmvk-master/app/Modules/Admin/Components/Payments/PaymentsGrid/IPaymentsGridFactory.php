<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Payments\PaymentsGrid;

interface IPaymentsGridFactory
{
    public function create(): PaymentsGrid;
}