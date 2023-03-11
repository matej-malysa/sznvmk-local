<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Users\AddUserForm;

use App\Components\Flashes\Flashes;
use App\Components\FormComponent;
use App\Model\FacultiesModel;
use App\Model\UserModel;
use Dibi\Exception;
use Nette\Application\UI\Form;
use Nette\Schema\ValidationException;
use Nette\Utils\ArrayHash;

class AddUserForm extends FormComponent
{
    /** @var UserModel */
    protected UserModel $userModel;

    /** @var FacultiesModel */
    protected FacultiesModel $facultiesModel;

    /** @var array */
    private array $allFacultiesToSelect;

    public function __construct(UserModel $userModel, FacultiesModel $facultiesModel)
    {
        parent::__construct();
        $this->userModel = $userModel;
        $this->facultiesModel = $facultiesModel;
        $this->allFacultiesToSelect = $this->facultiesModel->getAllToSelect();
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/form.latte');
        $this->template->facultiesToSelect = $this->allFacultiesToSelect;
        $this->template->render();
    }


    public function createComponentForm()
    {
        $form = parent::createComponentForm();
        $form->addText('username', 'Uživatelské jméno')
            ->setRequired();
        $form->addPassword('password', 'Heslo')
            ->setRequired();
        $form->addSelect('role', 'Uživatelská role', $this->userModel->getRolesToSelect())->setDefaultValue(3)
            ->addCondition(Form::IS_IN, [2, 3])
                ->toggle('instructors-info');

        $form->addSelect('faculty', 'Fakulta', $this->allFacultiesToSelect)->setPrompt('Vyberte fakultu');
        $form->addText('nickname', 'Přezdívka');
        $form->addTextArea('text', 'Představení');
        $form->addUpload('photo', 'Profilový obrázek');

        $form->addSubmit('send', 'Uložit');
        $form->addSubmit('cancel', 'Zrušit')
            ->setValidationScope([])
            ->onClick[] = [$this, 'cancelClicked'];

        return $form;
    }

    public function formSucceeded(Form $form, ArrayHash $values)
    {
        try {
            $photo = $values->photo;
            unset($values->photo);
            $id = $this->userModel->addInstructor($values);
            if (!$photo->error) {
                $path = WWW_DIR . '/images/instruktori/' . $id . '.jpg';
                $photo->move($path);
            }
            $this->flashMessage('Úspěšně uloženo', Flashes::FLASH_SUCCESS);
        } catch (ValidationException $ex) {
                $this->flashMessage('Chybný formát dat. Změny nebyly uloženy.', Flashes::FLASH_DANGER);
        } catch (Exception $ex) {
            $form->addError('Databázová chyba, změny nebyly uloženy');
        }

        $this->finishHandler();
    }
}