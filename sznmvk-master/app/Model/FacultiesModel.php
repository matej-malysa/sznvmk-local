<?php

declare(strict_types=1);

namespace App\Model;

use Dibi\Exception;
use Dibi\Fluent;
use Dibi\Row;

class FacultiesModel extends BaseModel
{
    const T_FACULTIES = 'faculties';
    const T_SCHOOLS = 'schools';

    const NO_SCHOOL_ID = 25;

    /**
     * @param int $id
     * @return Row|false
     */
    public function getById(int $id)
    {
        return $this->db->select('*')->from('%n', self::T_FACULTIES)->where('id = %i', $id)->fetch();
    }

    /**
     * @return array
     */
    public function getActiveToSelect(): array
    {
        return $this->db->select("faculties.id AS id, IF(faculties.id = 25, schools.name, CONCAT(schools.code, ' - ', faculties.name)) AS name")
            ->from("%n", self::T_FACULTIES)
            ->leftJoin('%n ON schools.id = faculties.school', self::T_SCHOOLS)
            ->where('faculties.enabled = 1')
            ->orderBy('id')
            ->fetchPairs();
    }

    /**
     * @return array
     */
    public function getAllToSelect(): array
    {
        return $this->db->select("faculties.id AS id, IF(faculties.id = 25, schools.name, CONCAT(schools.code, ' - ', faculties.name)) AS name")
            ->from("%n", self::T_FACULTIES)
            ->leftJoin('%n ON schools.id = faculties.school', self::T_SCHOOLS)
            ->orderBy('id')
            ->fetchPairs();
    }

    /**
     * @return array
     */
    public function getCodesToSelect(): array
    {
        return $this->db->select("id, IF(id = 25, 'Bez školy', code) AS name")
            ->from('%n', self::T_FACULTIES)
            ->orderBy('id')
            ->fetchPairs();
    }

    /**
     * @param int $schoolId
     * @return array
     */
    public function getAllBySchool(int $schoolId): array
    {
        return $this->db->select('*')->from('%n', self::T_FACULTIES)->where('school = %i', $schoolId)->fetchAll();
    }

    /**
     * @param int $facultyId
     * @return Row
     */
    public function getSchoolCodeAndFacultyName(int $facultyId): Row
    {
        return $this->db->select("schools.id AS schoolId, IF(faculties.id = 25, schools.name, CONCAT(schools.code, ' - ', faculties.name)) AS name")
            ->from('%n', self::T_FACULTIES)
            ->leftJoin('%n ON faculties.school = schools.id', self::T_SCHOOLS)
            ->where('faculties.id = %i', $facultyId)
            ->fetch();
    }

    /**
     * @param int $facultyId
     * @return string
     */
    public function getSchoolCodeAndFacultyCode(int $facultyId): string
    {
        return $this->db->select("IF(faculties.id = 25, schools.name, CONCAT(schools.code, ' - ', faculties.code)) AS name")
            ->from('%n', self::T_FACULTIES)
            ->leftJoin('%n ON faculties.school = schools.id', self::T_SCHOOLS)
            ->where('faculties.id = %i', $facultyId)
            ->fetchSingle();
    }

    /* GRID ********************************************************************************************************* */

    /**
     * @return Fluent
     */
    public function getGrid(): Fluent
    {
        return $this->db->select('faculties.id, faculties.name, faculties.code AS code, faculties.enabled, schools.code AS school')->from('%n', self::T_FACULTIES)
            ->leftJoin('%n', self::T_SCHOOLS)->on('faculties.school = schools.id');
    }

    /**
     * @param int $id
     * @param int $value
     * @throws Exception
     */
    public function changeEnabled(int $id, int $value)
    {
        $this->db->update(self::T_FACULTIES, ['enabled' => $value])->where('id = %i', $id)->execute();
    }
}