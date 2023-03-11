<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Graf\GrafGrid;

use App\Components\Flashes\Flashes;
use App\Components\FormComponent;
use App\Components\GridComponent\GridComponent;
use App\Model\ApplicationsModel;
use App\Model\FacultiesModel;
use App\Model\GraphModel;
use App\Model\TransportModel;
use App\Model\PaymentsModel;
use App\Modules\Admin\Presenters\GrafPresenter;
use Nette\Application\UI\Form;
use Nette\Schema\ValidationException;
use Nette\Utils\ArrayHash;
use Ublaboo\DataGrid\DataGrid;
use App\Model\UserModel;
use Dibi\Exception;
use Nette\Application\AbortException;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;
use Nette\Application\UI\Control;

class GrafGrid extends GridComponent
{
    /** @var GraphModel */
    protected GraphModel $graphModel;

    /** @var FacultiesModel */
    protected FacultiesModel $facultiesModel;

    /** @var TransportModel */
    protected TransportModel $transportModel;

    /** @var PaymentsModel */
    protected PaymentsModel $paymentsModel;

    protected string $fac = 'All';

    protected int $grafType = 1;


    public function __construct(GraphModel $graphModel, FacultiesModel $facultiesModel, TransportModel $transportModel, PaymentsModel $paymentsModel)
    {
        parent::__construct();
        $this->graphModel = $graphModel;
        $this->facultiesModel = $facultiesModel;
        $this->transportModel = $transportModel;
        $this->paymentsModel = $paymentsModel;

    }

    public function render()
    {
        $this->template->graf = $this->grafType;
        $this->template->fac = $this->fac;

        if($this->grafType == 1) {
            $this->template->Data = $this->graphModel->getAppAllCount($this->grafType,$this->fac);
            //$this->template->test = $this->graphModel->getDepStartEndDate();
            $this->template->faculties = $this->facultiesModel->getAllToSelect();
            $this->template->facultiesCode = $this->facultiesModel->getCodesToSelect();
        } else if($this->grafType == 2) {
            $this->template->Data = $this->graphModel->getAppAllCount($this->grafType,$this->fac);
            $this->template->faculties = $this->facultiesModel->getAllToSelect();
            $this->template->facultiesCode = $this->facultiesModel->getCodesToSelect();
        } else {
            $this->template->Data = $this->graphModel->getAppAllCount($this->grafType,$this->fac);
            $this->template->faculties = $this->facultiesModel->getAllToSelect();
            $this->template->facultiesCode = $this->facultiesModel->getCodesToSelect();
        }

        $this->template->setFile(__DIR__ . '/templates/grid.latte');
        $this->template->render();
    }

    public function handleUpdate(int $grafType, string $code)
    {
        $this->fac = $code;
        $this->grafType = $grafType;
        $this->redrawControl('data');
    }



}