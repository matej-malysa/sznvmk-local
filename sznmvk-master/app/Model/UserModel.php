<?php
declare(strict_types=1);

namespace App\Model;

use Dibi\Connection;
use Dibi\Exception;
use Dibi\Row;
use Nette\Schema\Elements\Structure;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Security\Passwords;
use Nette\Utils\ArrayHash;

class UserModel extends BaseModel
{
    const T_USERS = 'users';
    const T_INSTRUCTORS = 'instructors';
    const T_ROLE = 'role';
    const T_USER_TO_ROLE = 'user_to_role';


    /** @var Structure|Schema */
    protected Schema|Structure $addInstructorSchema;
    /** @var Structure|Schema */
    protected Schema|Structure $editInstructorSchema;
    /** @var Passwords */
    protected Passwords $passwords;


    public function __construct(Connection $connection, Passwords $passwords)
    {
        parent::__construct($connection);
        $this->passwords = $passwords;
        $this->addInstructorSchema = Expect::structure([
            'username' => Expect::string()->required(),
            'password' => Expect::string(),
            'role' => Expect::int(),
            'nickname' => Expect::string(),
            'text' => Expect::string(),
            'faculty' => Expect::int()->nullable(),
        ])->castTo(ArrayHash::class);
        $this->editInstructorSchema = Expect::structure([
            'nickname' => Expect::string()->required(),
            'text' => Expect::string(),
            'faculty' => Expect::int()->required(),
            'password' => Expect::string()->nullable(),
            'role' => Expect::int()->nullable(),
        ])->castTo(ArrayHash::class);
    }

    /**
     * @param int $id
     * @return Row|false
     */
    public function getById(int $id): Row|false
    {
        return $this->db->select('*')
            ->from('%n', self::T_USERS)
            ->leftJoin('%n AS ur ON users.id = ur.user_id', self::T_USER_TO_ROLE)
            ->where('id = %i', $id)
            ->fetch();
    }

    /**
     * @param string $username
     * @return array|Row|null
     */
    public function getByUsername(string $username)
    {
        return $this->db->select('*')
            ->from('%n', self::T_USERS)
            ->leftJoin('%n AS ur ON users.id = ur.user_id', self::T_USER_TO_ROLE)
            ->where('username = %s && enabled = 1', $username)
            ->fetch();
    }

    /**
     * @param int $id
     * @return array|Row|null
     */
    public function getInstructor(int $id)
    {
        return $this->db->select('*')->from('%n', self::T_INSTRUCTORS)->where('user_id = %i', $id)->fetch();
    }

    /**
     * @param int $id
     * @return array|Row|null
     */
    public function getInstructorWithUserDetails(int $id)
    {
        return $this->db->select('users.*, instructors.*, ur.role_id AS role')->from('%n', self::T_USERS)
            ->leftJoin('%n ON users.id = instructors.user_id', self::T_INSTRUCTORS)
            ->leftJoin('%n AS ur ON users.id = ur.user_id', self::T_USER_TO_ROLE)
            ->where('users.id = %i', $id)
            ->fetch();
    }


    /**
     * @return array
     */
    public function getInstructorsAll(bool $admin): array
    {
        $out = $this->db->select('users.id AS userId, users.username, role.name AS role, role.id AS roleId, ins.*, fac.name AS facultyName, fac.code AS facultyCode, sch.code AS schoolCode')
            ->from('%n', self::T_USERS)
            ->leftJoin('%n AS ins ON users.id = ins.user_id', self::T_INSTRUCTORS)
            ->leftJoin('%n AS fac ON fac.id = ins.faculty', FacultiesModel::T_FACULTIES)
            ->leftJoin('%n AS sch ON fac.school = sch.id', FacultiesModel::T_SCHOOLS)
            ->leftJoin('%n AS ur ON users.id = ur.user_id', self::T_USER_TO_ROLE)
            ->leftJoin('%n ON ur.role_id = role.id', self::T_ROLE)
            ->where('users.id != 1')
            ->orderBy('userId');

        if (!$admin) {
            $out->where('ur.role_id != 1');
        }

        return $out->fetchAll();
    }

    /**
     * @return array
     */
    public function getInstructorsForPage(): array
    {
        return $this->db->select('ins.user_id, ins.nickname, ins.text, fac.name AS facultyName, fac.code AS facultyCode, sch.code AS schoolCode')
            ->from('%n AS ins', self::T_INSTRUCTORS)
            ->leftJoin('%n AS fac ON fac.id = ins.faculty', FacultiesModel::T_FACULTIES)
            ->leftJoin('%n AS sch ON fac.school = sch.id', FacultiesModel::T_SCHOOLS)
            ->orderBy('user_id')
            ->fetchAll();
    }

    /**
     * @param int $id
     * @return bool
     */
    public function hasProfilePic(int $id): bool
    {
        return file_exists(sprintf('%s/images/instruktori/%s.jpg', WWW_DIR, $id));
    }

    /**
     * @param array $instruktori
     * @return array
     */
    public function getProfilePics(iterable $instruktori)
    {
        $profilePics = [];
        foreach ($instruktori as $instruktor) {
            $relPath = sprintf('../images/instruktori/%s.jpg', $instruktor->user_id);
            if (file_exists(sprintf('%s/images/instruktori/%s.jpg', WWW_DIR, $instruktor->user_id))) {
                $profilePics[$instruktor->user_id] = $relPath;
            } else {
                $profilePics[$instruktor->user_id] = sprintf('../images/instruktori/no_photo.jpg');
            }
        }
        return $profilePics;
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function getProfilePic(int $id)
    {
        $instruktor[]['user_id'] = $id;
        $instruktor = ArrayHash::from($instruktor);
        return $this->getProfilePics($instruktor)[$id];
    }

    /**
     * @param ArrayHash $values
     * @return int
     * @throws Exception
     */
    public function addInstructor(ArrayHash $values): int
    {
        $values = $this->validate($this->addInstructorSchema, $values);

        // Create system access
        $values['password'] = $this->passwords->hash($values['password']);
        $this->db->insert(self::T_USERS, ['username' => $values->username, 'password' => $values->password])->execute();
        $id = $this->db->getInsertId();
        $this->db->insert(self::T_USER_TO_ROLE, ['user_id' => $id, 'role_id' => $values->role])->execute();
        if (in_array($values['role'], [2,3])) {
            // Create instructors detail
            $this->db->insert(self::T_INSTRUCTORS, ['user_id' => $id, 'nickname' => $values->nickname, 'faculty' => $values->faculty, 'text' => $values->text])->execute();
        }
        return $id;
    }

    /**
     * @param int $id
     * @param ArrayHash $values
     * @throws Exception
     */
    public function editInstructor(int $id, ArrayHash $values): void
    {
        unset($values->photo, $values->username);
        $values = $this->validate($this->editInstructorSchema, $values);
        if ($values->role) {
            $this->db->update(self::T_USER_TO_ROLE, ['role_id' => $values->role])->where('user_id = %i', $id)->execute();
            unset($values->role);
        }
        if (!empty($values->password)) {
            $values['password'] = $this->passwords->hash($values['password']);
            $this->db->update(self::T_USERS, ['password' => $values->password])->where('id = %i', $id)->execute();
            unset($values->password);
        }

        $this->db->update(self::T_INSTRUCTORS, $values)->where('user_id = %i', $id)->execute();
    }

    /**
     * @param int $id
     * @throws Exception
     */
    public function deleteUser(int $id): void
    {
        $this->db->delete(self::T_USERS)->where('id = %i', $id)->execute();
    }

    /**
     * @param int $id
     * @throws Exception
     */
    public function deleteInstructor(int $id): void
    {
        $this->db->delete(self::T_INSTRUCTORS)->where('user_id = %i', $id)->execute();
        $this->deleteUser($id);
    }

    public function getRolesToSelect(): array
    {
        return $this->db->select('id, name')->from('%n', self::T_ROLE)->fetchPairs();
    }
}