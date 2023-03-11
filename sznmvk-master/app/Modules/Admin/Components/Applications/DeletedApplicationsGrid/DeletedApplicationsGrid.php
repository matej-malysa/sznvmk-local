<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Applications\DeletedApplicationsGrid;

use App\Components\Flashes\Flashes;
use App\Modules\Admin\Components\Applications\ApplicationsGrid\ApplicationsGrid;
use Dibi\Exception;
use Ublaboo\DataGrid\DataGrid;

class DeletedApplicationsGrid extends ApplicationsGrid
{
    public function createComponentGrid(): Datagrid
    {
        $grid = parent::createComponentGrid();
        $grid->setDataSource($this->applicationsModel->getApplicationsDeleted());
        $grid->removeFilter('statusName');
        $grid->addAction('restore', '', 'restore!', ['id' => 'id'])
            ->setIcon('infinity')
            ->setClass('btn btn-xs btn-success ajax')
            ->setTitle('Obnovit smazanou přihlášku');
        return $grid;
    }


    public function handleRestore(int $id): void
    {
        try {
            $this->applicationsModel->restoreApplication($id, $this->presenter->getUser()->id);
            $this->logger->info('Application restored', ['id' => $id, 'restored_by' => $this->presenter->getUser()->id]);
            $this->flashMessage('Přihláška úspěšně obnovena', Flashes::FLASH_SUCCESS);
        } catch (Exception $ex) {
            $this->flashMessage('Chyba při obnovení přihlášky', Flashes::FLASH_DANGER);
        }

        $this->finishHandler();
    }

}