<?php
declare(strict_types=1);

namespace App\Model;

use Dibi\Exception;

class AllergiesModel extends BaseModel
{
    const T_ALLERGIES = 'allergies';
    const T_ALLERGY_TO_APPLICATION = 'allergy_to_application';

    /**
     * @return array
     */
    public function getAllToSelect(): array
    {
        return $this->db->select('*')->from('%n',self::T_ALLERGIES)->fetchAssoc('id');
    }

    /**
     * @param int $id
     * @return string|null
     */
    public function getNameById(int $id): string|null
    {
        return $this->db->select('name')->from('%n',self::T_ALLERGIES)->where('id = %i', $id)->fetchSingle();
    }

    /**
     * @param int $id
     * @param string $column
     * @return array
     */
    public function getAllergybyId(int $id, string $column): array
    {
        $user = $this->db->select('allergy_id')->from('%n', self::T_ALLERGY_TO_APPLICATION)->where('application_id = %i', $id)->fetchAll();
        $allergies = array();
        foreach($user as $users_allergy) {
            $allergy = $this->db->select($column)->from('%n',self::T_ALLERGIES)->where('id = %i', $users_allergy)->fetch();
            array_push($allergies, $allergy->$column);
        }
        return $allergies;
    }

    /**
     * @param array $values
     * @param int $applicationId
     * @throws Exception
     */
    public function editAllergies(Array $values, int $applicationId)
    {
        $user_allergies = $this->getAllergybyId($applicationId, 'id');
        $rozdil1 = array_diff($values, $user_allergies); // hodnoty z formu, ktere nejsou v databazi
        $rozdil2 = array_diff($user_allergies, $values); // hodnoty z databaze, ktere nebyly v poli z formu
        if($rozdil1 != NULL) {
            foreach($rozdil1 as $allergy_id) {
                $allergy = [
                    'application_id' => $applicationId,
                    'allergy_id' => $allergy_id,
                ];
                $this->db->insert(self::T_ALLERGY_TO_APPLICATION, $allergy)->execute();
            }
        }

        if($rozdil2 != NULL) {
            foreach($rozdil2 as $allergy_id) {
                $allergy = [
                    'application_id' => $applicationId,
                    'allergy_id' => $allergy_id,
                ];
                $this->db->delete(self::T_ALLERGY_TO_APPLICATION)->where($allergy)->execute();
            }
        }
    }
}