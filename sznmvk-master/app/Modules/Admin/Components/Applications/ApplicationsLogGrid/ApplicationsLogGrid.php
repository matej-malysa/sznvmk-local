<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Applications\ApplicationsLogGrid;

use App\Components\GridComponent\GridComponent;
use App\Model\ApplicationsModel;
use Ublaboo\DataGrid\DataGrid;

class ApplicationsLogGrid extends GridComponent
{
    /** @var ApplicationsModel */
    protected ApplicationsModel $applicationModel;

    /** @var int */
    protected int $applicationId;

    public function __construct(ApplicationsModel $applicationsModel, int $applicationId)
    {
        parent::__construct();
        $this->applicationModel = $applicationsModel;
        $this->applicationId = $applicationId;
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
        $grid->setDataSource($this->applicationModel->getApplicationsLogGrid($this->applicationId));
        $grid->setPagination(false);
        $grid->addColumnDateTime('created_at', 'Kdy')
            ->setFormat('j. n. Y H:i:s')
            ->setAlign('left');
        $grid->addColumnText('text', 'Akce');
        $grid->addColumnText('column', 'Sloupec')
            ->setReplacement([
                'firstname' => 'Jméno',
                'lastname' => 'Příjmení',
                'phone' => 'Telefon',
                'birthdate' => 'Datum narození',
                'faculty' => 'Fakulta',
                'transport' => 'Způsob dopravy',
                'session' => 'Turnus',
                'bonus' => 'Bonus',
                'spz' => 'SPZ',
                'gender' => 'Pohlaví',
            ]);
        $grid->addColumnText('old_value', 'Původní hodnota');
        $grid->addColumnText('new_value', 'Nová hodnota');
        $grid->addColumnText('username', 'Kým')
            ->setReplacement([
                'system' => 'Automaticky/Uživatelem',
            ]);

        return $grid;
    }
}
