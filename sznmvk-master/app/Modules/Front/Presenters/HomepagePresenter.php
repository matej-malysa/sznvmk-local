<?php

declare(strict_types=1);

namespace App\Modules\Front\Presenters;

use App\Model\ImportantDatesModel;
use App\Model\PaymentsLimitsModel;
use App\Modules\Front\Components\RegistrationForm\IRegistrationFormFactory;
use App\Modules\Front\Components\RegistrationForm\RegistrationForm;

class HomepagePresenter extends BasePresenter
{
    /** @var PaymentsLimitsModel @inject */
    public PaymentsLimitsModel $paymentsLimitsModel;

    /** @var ImportantDatesModel @inject */
    public ImportantDatesModel $importantDatesModel;

    /** @var IRegistrationFormFactory @inject */
    public IRegistrationFormFactory $registrationFormFactory;

    public function actionDefault()
    {
        $this->template->cena = $this->paymentsLimitsModel->getAll()->fetchAssoc('id');
        $dates = $this->importantDatesModel->getAll()->fetchAssoc('id');
        $this->template->deadlines = $this->importantDatesModel->getCzechTextDates($dates);
    }

    /**
     * @return RegistrationForm
     */
    public function createComponentRegistrationForm(): RegistrationForm
    {
        return $this->registrationFormFactory->create();
    }
}
