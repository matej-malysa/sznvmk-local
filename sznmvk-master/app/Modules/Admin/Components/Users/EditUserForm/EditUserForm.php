<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Users\EditUserForm;

use App\Components\Flashes\Flashes;
use App\Components\FormComponent;
use App\Model\FacultiesModel;
use App\Model\UserModel;
use Dibi\Exception;
use Nette\Application\UI\Form;
use Nette\Schema\ValidationException;
use Nette\Utils\ArrayHash;

class EditUserForm extends FormComponent
{
    /** @var FacultiesModel */
    protected FacultiesModel $facultiesModel;

    /** @var UserModel */
    protected UserModel $userModel;

    /** @var int */
    protected int $id;

    /** @var array */
    private array $facultiesToSelect;

    /** @var string */
    private $uploadedFile;

    public function __construct(FacultiesModel $facultiesModel, UserModel $userModel, int $id)
    {
        parent::__construct();
        $this->facultiesModel = $facultiesModel;
        $this->userModel = $userModel;
        $this->id = $id;
        $this->facultiesToSelect = $this->facultiesModel->getAllToSelect();
        $this->uploadedFile = $this->userModel->getProfilePic($this->id);
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/form.latte');
        $this->template->facultiesToSelect = $this->facultiesToSelect;
        $this->template->profilePic = $this->userModel->hasProfilePic($this->id);
        $this->template->leader = $this->isLeader();
        $this->template->render();
    }

    public function createComponentForm()
    {
        $form = parent::createComponentForm();
        $form->addText('username', 'Uživatelské jméno');
        $form->addPassword('password', 'Heslo');
        $form->addSelect('role', 'Uživatelská role', $this->userModel->getRolesToSelect())
            ->addCondition(Form::IS_IN, [2, 3])
            ->toggle('instructors-info');

        $form->addSelect('faculty', 'Fakulta', $this->facultiesToSelect);
        $form->addText('nickname', 'Přezdívka');
        $form->addTextArea('text', 'Představení');
        $form->addUpload('photo', 'Profilový obrázek')
            ->setOption('description', $this->uploadedFile);

        $form->addSubmit('send', 'Uložit změny');
        $form->addSubmit('cancel', 'Zrušit')
            ->setValidationScope([])
            ->onClick[] = [$this, 'cancelClicked'];

        $form->setDefaults($this->userModel->getInstructorWithUserDetails($this->id));

        return $form;
    }

    public function formSucceeded(Form $form, ArrayHash $values)
    {
        try {
            if ($values['photo']->isOk()) {
                $path = WWW_DIR . '/images/instruktori/' . $this->id . '.jpg';
                $values->photo->move($path);
            }
            $this->userModel->editInstructor($this->id, $values);
            $this->flashMessage('Úspěšně uloženo', Flashes::FLASH_SUCCESS);
        } catch (ValidationException $ex) {
            $this->flashMessage('Chybný formát dat. Změny nebyly uloženy.', Flashes::FLASH_DANGER);
        } catch (Exception $ex) {
            $form->addError('Databázová chyba, změny nebyly uloženy');
        }

        $this->finishHandler($this->redirectDest);
    }
}