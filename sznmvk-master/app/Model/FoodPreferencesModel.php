<?php
declare(strict_types=1);

namespace App\Model;

use Dibi\Exception;
use Dibi\Fluent;

class FoodPreferencesModel extends BaseModel
{
    const T_FOOD_PREFERENCES = 'food_preferences';
    const T_FOOD_PREFERENCES_TO_APPLICATION = 'food_preferences_to_application';

    /**
     * @return array
     */
    public function getAllToSelect(): array
    {
        return $this->db->select('*')->from('%n', self::T_FOOD_PREFERENCES)->fetchAssoc('id');
    }

    /**
     * @param int $id
     * @param string $column
     * @return array
     */
    public function getFoodPreferenceById(int $id, string $column): array
    {
        $user = $this->db->select('food_preferences_id')->from('%n', self::T_FOOD_PREFERENCES_TO_APPLICATION)->where('application_id = %i', $id)->fetchAll();
        $preferences = [];
        foreach($user as $users_food_preferences) {
            $food_preference = $this->db->select($column)->from('%n',self::T_FOOD_PREFERENCES)->where('id = %i', $users_food_preferences)->fetch();
            array_push($preferences, $food_preference->$column);
        }
        return $preferences;
    }

    /**
     * @param int $id
     * @return ?string
     */
    public function getNameById(int $id): string|null
    {
        return $this->db->select('name')->from('%n', self::T_FOOD_PREFERENCES)->where('id = %i', $id)->fetchSingle();
    }

    /**
     * @return Fluent
     */
    public function getAll(): Fluent
    {
        return $this->db->select('*')->from('%n', self::T_FOOD_PREFERENCES)->orderBy('id ASC');
    }

    /**
     * @param array $values
     * @param int $applicationId
     * @throws Exception
     */
    public function editFoodPreferences(array $values, int $applicationId)
    {
        $user_food_preferences = $this->getFoodPreferenceById($applicationId, 'id');
        $rozdil1 = array_diff($values, $user_food_preferences); // hodnoty z formu, ktere nejsou v databazi
        $rozdil2 = array_diff($user_food_preferences, $values); // hodnoty z databaze, ktere nebyly v poli z formu
        if($rozdil1 != NULL) {
            foreach($rozdil1 as $food_preference_id) {
                $food_preference = [
                    'application_id' => $applicationId,
                    'food_preferences_id' => $food_preference_id,
                ];
                $this->db->insert(self::T_FOOD_PREFERENCES_TO_APPLICATION, $food_preference)->execute();
            }
        }

        if($rozdil2 != NULL) {
            foreach($rozdil2 as $food_preference_id) {
                $food_preference = [
                    'application_id' => $applicationId,
                    'food_preferences_id' => $food_preference_id,
                ];
                $this->db->delete(self::T_FOOD_PREFERENCES_TO_APPLICATION)->where($food_preference)->execute();
            }
        }

    }


}