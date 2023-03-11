<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Applications\ApplicationsGrid;

use App\Components\GridComponent\GridComponent;
use App\Model\ApplicationsModel;
use App\Model\FacultiesModel;
use App\Model\TransportModel;
use Ublaboo\DataGrid\DataGrid;

class ApplicationsGrid extends GridComponent
{
    /** @var ApplicationsModel */
    protected $applicationsModel;

    /** @var FacultiesModel */
    protected $facultiesModel;

    /** @var TransportModel */
    protected $transportModel;

    public function __construct(ApplicationsModel $applicationsModel, FacultiesModel $facultiesModel, TransportModel $transportModel)
    {
        parent::__construct();
        $this->applicationsModel = $applicationsModel;
        $this->facultiesModel = $facultiesModel;
        $this->transportModel = $transportModel;
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/grid.latte');
        $this->template->render();
    }

    public function createComponentGrid(): Datagrid
    {
        $grid = parent::createComponentGrid();
        $grid->setPrimaryKey('id');
        $grid->setDefaultPerPage(50);
        $grid->setDataSource($this->applicationsModel->getApplicationsActive());

        $grid->addColumnNumber('id', 'ID')
            ->setSortable();
        $grid->addColumnText('firstname', 'Křestní jméno')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('lastname', 'Příjmení')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('email', 'E-mail')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('statusName', 'Status')
            ->setSortable()
            ->setFilterSelect($this->applicationsModel->getApplicationStatusesToSelectNotDeleted(), 'status')
            ->setPrompt('');
        $grid->addColumnText('phone', 'Telefonní číslo')
            ->setTemplate(__DIR__ . '/templates/phone_link.latte')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('facultyCode', 'Fakulta')
            ->setSortable()
            ->setFilterSelect($this->facultiesModel->getCodesToSelect(), 'faculty')
            ->setPrompt('');
        $grid->addColumnText('transportName', 'Způsob dopravy')
            ->setSortable()
            ->setReplacement(['' => 'Nezadáno'])
            ->setFilterSelect($this->transportModel->getAllToSelect(), 'transport')
            ->setPrompt('');

        $grid->setColumnsHideable();
        $grid->addAction('detail', '', 'Applications:detail', ['id'])->setIcon('search')
             ->setTitle("Detail přihlášky");

        return $grid;
    }
}