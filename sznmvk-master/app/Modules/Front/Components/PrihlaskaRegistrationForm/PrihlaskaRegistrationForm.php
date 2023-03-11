<?php
declare(strict_types=1);

namespace App\Modules\Front\Components\PrihlaskaRegistrationForm;

use App\Modules\Front\Components\RegistrationForm\RegistrationForm;

class PrihlaskaRegistrationForm extends RegistrationForm
{
    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/form.latte');
        $this->template->facultiesToSelect = $this->facultiesToSelect;
        $this->template->sessionsToSelect = $this->sessionsToSelect;
        $this->template->gendersToSelect = $this->gendersToSelect;
        $this->template->transportsToSelect = $this->transportsToSelect;
        if (!empty($this->sessionsToSelect)) {
            $this->template->render();
        }
    }
}
