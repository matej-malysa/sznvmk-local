<?php
declare(strict_types=1);

namespace App\Modules\Admin\Presenters;

use App\Model\ApplicationsModel;
use XSuchy09\Application\Responses\CsvResponse;

class ExportsPresenter extends BasePresenter
{
    /** @var ApplicationsModel @inject */
    public ApplicationsModel $applicationsModel;

    public function actionApplicationsNotPaid()
    {
        $this->template->emails = $this->applicationsModel->getEmailsOfNotPaidApplications();
    }


    /**
     * @throws \Nette\Application\AbortException
     * @secured
     */
    public function handleApplicationAllDetails()
    {
        $data = $this->applicationsModel->exportApplicationsEverything();
        $response = new CSVResponse($data);
        $this->sendResponse($response);
    }
}