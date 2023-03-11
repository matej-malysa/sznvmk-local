<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Users\UsersGrid;

use App\Components\Flashes\Flashes;
use App\Components\GridComponent\GridComponent;
use App\Model\UserModel;
use Dibi\Exception;
use Dibi\Row;
use Nette\Application\AbortException;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;
use Ublaboo\DataGrid\DataGrid;

class UsersGrid extends GridComponent
{
    /** @var UserModel */
    protected UserModel $userModel;

    public function __construct(UserModel $userModel)
    {
        parent::__construct();
        $this->userModel = $userModel;
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/grid.latte');
        $this->template->render();
    }

    public function createComponentGrid(): Datagrid
    {
        $grid = parent::createComponentGrid();
        $grid->setPrimaryKey('userId');
        $grid->setDataSource($this->userModel->getInstructorsAll($this->isAdmin()));
        $grid->addColumnNumber('role', 'Role')
            ->setSortable()
            ->setFilterSelect($this->userModel->getRolesToSelect(), 'roleId')
            ->setPrompt('');
        $grid->addColumnText('username', 'Uživatelské jméno')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('nickname', 'Přezdívka')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('schoolCode', 'Škola')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('facultyName', 'Fakulta')
            ->setSortable()
            ->setFilterText();

        $grid->addAction('edit', '', 'Users:edit', ['id' => 'userId'])
             ->setIcon('pen')
             ->setTitle("Upravit uživatele");
        $grid->addAction('delete', '', 'delete!', ['id' => 'userId'])
            ->setIcon('trash')
            ->setTitle('Smazat')
            ->setClass('btn btn-xs btn-danger ajax')
            ->setConfirmation(
                new StringConfirmation('Opravdu chcete smazat uživatele %s?', 'username')
            );

        $grid->allowRowsAction('edit', function (Row $item): bool {
            switch ($this->presenter->user->getRoles()[0]) {
                case 1:
                    return true;
                case 2:
                    return $item->roleId !== 1;
                case 3:
                case 4:
                    return false;
            }
        });

        $grid->allowRowsAction('delete', function (Row $item): bool {
            switch ($this->presenter->user->getRoles()[0]) {
                case 1:
                    return true;
                case 2:
                    return $item->roleId !== 1;
                case 3:
                case 4:
                    return false;
            }
        });


        return $grid;
    }

    /**
     * @param int $id
     * @throws AbortException
     * @secured
     */
    public function handleDelete(int $id): void
    {
        try {
            $user = $this->userModel->getById($id);
            if (in_array($user['role_id'], [2,3])) {
                $this->userModel->deleteInstructor($id);
                $this->flashMessage('Instruktor smazán', Flashes::FLASH_SUCCESS);
            } else {
                $this->userModel->deleteUser($id);
                $this->flashMessage('Uživatel smazán', Flashes::FLASH_SUCCESS);
            }

            $this->logger->info('User deleted', ['id' => $id, 'username' => $user['username'], 'deleted_by' => $this->presenter->getUser()->id]);
        } catch (Exception $ex) {
            $this->flashMessage('Chyba při mazání instruktora. Instruktor nebyl smazán.', Flashes::FLASH_DANGER);
        }

        $this->finishHandler();
    }
}