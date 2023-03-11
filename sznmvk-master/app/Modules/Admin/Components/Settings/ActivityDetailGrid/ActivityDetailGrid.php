<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\ActivityDetailGrid;

use App\Components\GridComponent\GridComponent;
use App\Model\ActivitiesModel;
use Ublaboo\DataGrid\DataGrid;

class ActivityDetailGrid extends GridComponent
{
    /* @var ActivitiesModel */
    protected ActivitiesModel $activitiesModel;

    /** @var int */
    protected int $id;

    public function __construct(ActivitiesModel $activitiesModel, int $id)
    {
        parent::__construct();
        $this->activitiesModel = $activitiesModel;
        $this->id = $id;
    }

    public function render()
    {
        $this->template->id = $this->id;
        $this->template->setFile(__DIR__ . '/templates/grid.latte');
        $this->template->render();
    }

    public function createComponentGrid(): Datagrid
    {

        $grid = parent::createComponentGrid();
        $grid->setPrimaryKey('id');
        $grid->setDataSource($this->activitiesModel->getParticipantsForActivity($this->id));
        $grid->setPagination(false);

        $grid->addColumnText('id', 'ID');
        $grid->addColumnText('firstname', 'Jméno');
        $grid->addColumnText('lastname', 'Příjmení');
        $grid->addColumnText('phone', 'Telefon');


        return $grid;
    }
}