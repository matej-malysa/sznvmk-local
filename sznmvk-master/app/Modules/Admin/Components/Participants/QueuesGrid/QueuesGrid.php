<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Participants\QueuesGrid;

use App\Components\GridComponent\GridComponent;
use App\Model\ParticipantsModel;
use App\Model\SessionsModel;
use Ublaboo\DataGrid\DataGrid;

class QueuesGrid extends GridComponent
{
    /** @var ParticipantsModel */
    protected ParticipantsModel $participantsModel;

    /** @var SessionsModel */
    protected SessionsModel $sessionsModel;

    public function __construct(ParticipantsModel $participantsModel, SessionsModel $sessionsModel)
    {
        parent::__construct();
        $this->participantsModel = $participantsModel;
        $this->sessionsModel = $sessionsModel;
    }


    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/grid.latte');
        $this->template->render();
    }

    public function createComponentGrid(): Datagrid
    {
        $grid = parent::createComponentGrid();
        $grid->setPrimaryKey('application_id');
        $grid->setDataSource($this->participantsModel->getQueuesGrid());

        $grid->addColumnText('sessionName', 'Turnus')
            ->setSortable()
            ->setFilterSelect($this->sessionsModel->getAllToSelect(), 'session')
            ->setPrompt('');
        $grid->addColumnNumber('queueOrder', 'Pořadí')
            ->setSortable();
        $grid->addColumnText('lastname', 'Příjmení')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('firstname', 'Křestní jméno')
            ->setSortable()
            ->setFilterText();
        $grid->addAction('detail', '', 'Applications:detail', ['id' => 'application_id'])
            ->setIcon('search')
            ->setTitle("Detail přihlášky");


        return $grid;
    }
}