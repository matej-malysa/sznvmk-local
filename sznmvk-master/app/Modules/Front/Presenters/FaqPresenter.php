<?php

declare(strict_types=1);

namespace App\Modules\Front\Presenters;

use App\Model\ImportantDatesModel;

class FaqPresenter extends BasePresenter
{
    /** @var ImportantDatesModel @inject */
    public ImportantDatesModel $importantDatesModel;

    public function actionDefault()
    {
        $this->template->deadlines = $this->importantDatesModel->getAll()->fetchAssoc('id');
    }
}