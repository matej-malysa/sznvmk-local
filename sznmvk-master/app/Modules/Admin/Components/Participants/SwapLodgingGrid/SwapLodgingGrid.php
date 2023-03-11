<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Participants\SwapLodgingGrid;

use App\Components\GridComponent\GridComponent;
use App\Model\ParticipantsModel;
use App\Model\SessionsModel;
use App\Model\LodgingModel;
use Ublaboo\DataGrid\DataGrid;

class SwapLodgingGrid extends GridComponent
{
    /** @var ParticipantsModel */
    protected ParticipantsModel $participantsModel;

    /** @var SessionsModel */
    protected SessionsModel $sessionsModel;

    /** @var LodgingModel */
    protected LodgingModel $lodgingModel;

    public int $id;

    public function __construct(ParticipantsModel $participantsModel, SessionsModel $sessionsModel, LodgingModel $lodgingModel, int $id)
    {
        parent::__construct();
        $this->participantsModel = $participantsModel;
        $this->sessionsModel = $sessionsModel;
        $this->lodgingModel = $lodgingModel;
        $this->id = $id;
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/grid.latte');
        $this->template->render();
    }

    public function createComponentGrid(): Datagrid
    {
        $grid = parent::createComponentGrid();
        $grid->setPrimaryKey('swapid');
        $grid->setDataSource($this->lodgingModel->getSwapLodgingById($this->id));
        $grid->addColumnNumber('swapid','ID');

        $grid->addColumnText('firstname','Meno')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('lastname','Priezvisko')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('faculty','Fakulta')
            ->setSortable();
        $grid->addColumnText('mainname','Název')
            ->setSortable();
        $grid->addColumnText('type','Typ ubytování')
            ->setSortable()
            ->setFilterSelect($this->lodgingModel->getTypeToSelect2(),'type')
            ->setPrompt('');
        $grid->addColumnText('space', 'Obsadenosť');
        $grid->addAction('makeSwap', '', 'Participants:makeSwap', ['lod1Id' => 'mainid','user1Id' => 'user','user2Id' => 'swapid','lod2Id' => 'user_room'])
            ->setIcon('edit')
            ->setTitle("Detail chatky") ;

        return $grid;
    }
}
