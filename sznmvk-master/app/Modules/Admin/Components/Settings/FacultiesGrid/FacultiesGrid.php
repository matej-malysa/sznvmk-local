<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\FacultiesGrid;

use App\Components\Flashes\Flashes;
use App\Components\GridComponent\GridComponent;
use App\Model\FacultiesModel;
use Dibi\Exception;
use Nette\Application\AbortException;
use Ublaboo\DataGrid\DataGrid;

class FacultiesGrid extends GridComponent
{
    /** @var FacultiesModel */
    protected $facultiesModel;

    /**
     * FacultiesGrid constructor.
     * @param FacultiesModel $facultiesModel
     */
    public function __construct(FacultiesModel $facultiesModel)
    {
        parent::__construct();
        $this->facultiesModel = $facultiesModel;
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
        $grid->setDataSource($this->facultiesModel->getGrid());

        $grid->addColumnText('name', 'Název')
            ->setSortable()
            ->setReplacement([
                '' => 'Jiná / bez školy',
            ])
            ->setFilterText('faculties.name');
        $grid->addColumnText('code', 'Kód')
            ->setSortable()
            ->setFilterText('faculties.code');
        $grid->addColumnText('school', 'Škola')
            ->setSortable()
            ->setFilterSelect(['' => 'Všechny', 1 => 'Bez školy', 2 => 'MUNI', 3 => 'VUT', 4 => 'MENDELU']);
        $grid->addColumnText('enabled', 'Stav')->setSortable()
            ->setReplacement([
                '0' => 'Zakázáno',
                '1' => 'Povoleno',
            ]);

        $grid->addAction('statusChange', '', 'statusChange!', ['id', 'old' => 'enabled'])
            ->setClass('btn btn-xs btn-primary')
            ->setIcon('toggle-on')
            ->setTitle('Změnit stav');

        return $grid;
    }

    /**
     * @param int $id
     * @param int $old
     * @throws AbortException
     */
    public function handleStatusChange(int $id, int $old): void
    {
        try {
            $new = $old == 1 ? 0 : 1;
            $this->facultiesModel->changeEnabled($id, $new);
            $this->flashMessage('Status fakulty změněn', Flashes::FLASH_SUCCESS);
        } catch (Exception $ex) {
            $this->flashMessage('Databázová chyba, změny nebyly uloženy', Flashes::FLASH_DANGER);
        }

        $this->finishHandler();
    }
}