<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\PaymentsLimitsGrid;

interface IPaymentsLimitsGridFactory
{
    /**
     * @return PaymentsLimitsGrid
     */
    public function create(): PaymentsLimitsGrid;
}