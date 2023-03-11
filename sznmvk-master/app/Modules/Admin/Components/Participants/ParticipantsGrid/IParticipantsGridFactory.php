<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Participants\ParticipantsGrid;

interface IParticipantsGridFactory
{
    /**
     * @return ParticipantsGrid
     */
    public function create(): ParticipantsGrid;
}