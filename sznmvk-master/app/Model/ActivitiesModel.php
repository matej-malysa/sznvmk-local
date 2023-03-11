<?php
declare(strict_types=1);

namespace App\Model;

use Dibi\Exception;
use Dibi\Fluent;

class ActivitiesModel extends BaseModel
{
    const T_ACTIVITIES = 'activities';
    const T_ACTIVITY_TO_APPLICATION = 'activity_to_application';

    /**
     * @return array
     */
    public function getAllToSelect(): array
    {
        return $this->db->select('*')->from('%n', self::T_ACTIVITIES)->fetchAssoc('id');
    }

    /**
     * @return array
     */
    public function getParticipantsForActivity(int $id): array
    {
        $activity_to_applications = $this->db->select('*')->from('%n', self::T_ACTIVITY_TO_APPLICATION)->where('activity_id = %i', $id)->fetchAll();
        $applications = [];
        foreach($activity_to_applications as $activity) {
            $activity = $this->db->select('id, firstname, lastname, phone')->from('%n',ApplicationsModel::T_APPLICATIONS)->where('id = %i', $activity->application_id)->fetch();
            array_push($applications, $activity);
        }

        return $applications;
    }

    /**
     * @param int $id
     * @param string $column
     * @return array
     */
    public function getActivitybyId(int $id, string $column): array
    {
        $user = $this->db->select('activity_id')->from('%n', self::T_ACTIVITY_TO_APPLICATION)->where('application_id = %i', $id)->fetchAll();
        $activities = [];
        foreach($user as $users_activity) {
            $activity = $this->db->select($column)->from('%n',self::T_ACTIVITIES)->where('id = %i', $users_activity)->fetch();
            array_push($activities, $activity->$column);
        }
        return $activities;
    }

    /**
     * @param int $id
     * @return ?string
     */
    public function getNameById(int $id): string|null
    {
        return $this->db->select('name')->from('%n', self::T_ACTIVITIES)->where('id = %i', $id)->fetchSingle();
    }

    /**
     * @return Fluent
     */
    public function getAll(): Fluent
    {
        return $this->db->select('*')->from('%n', self::T_ACTIVITIES)->orderBy('id ASC');
    }

    /**
     * @param array $values
     * @param int $applicationId
     * @throws Exception
     */
    public function editActivities(array $values, int $applicationId)
    {
        $user_activities = $this->getActivitybyId($applicationId, 'id');
        $new_insert = array_diff($values, $user_activities); // hodnoty z formu, ktere nejsou v databazi
        $new_delete = array_diff($user_activities, $values); // hodnoty z databaze, ktere nebyly v poli z formu
        if($new_insert != NULL) {
            foreach($new_insert as $activity_id) {
                $activity = [
                    'application_id' => $applicationId,
                    'activity_id' => $activity_id
                ];
                $this->db->insert(self::T_ACTIVITY_TO_APPLICATION, $activity)->execute();
            }
        }

        if($new_delete != NULL) {
            foreach($new_delete as $activity_id) {
                $activity = [
                    'application_id' => $applicationId,
                    'activity_id' => $activity_id
                ];
                $this->db->delete(self::T_ACTIVITY_TO_APPLICATION)->where($activity)->execute();
            }
        }
    }

    /**
     * @param int $id
     * @param int $value
     * @throws Exception
     */
    public function changeEnabled(int $id, int $value)
    {
        $this->db->update(self::T_ACTIVITIES, ['full' => $value])->where('id = %i', $id)->execute();
    }

    /**
     * @param int $id
     * @param int $value
     * @throws Exception
     */
    public function editActivity(int $id, int $value)
    {
        $this->db->update(self::T_ACTIVITIES, ['max_capacity' => $value])->where('id = %i', $id)->execute();
    }

    /**
     * @param int $id
     * @throws Exception
     */
    public function deleteActivity(int $id)
    {
        $this->db->delete(self::T_ACTIVITIES)->where('id = %i', $id)->execute();
    }

}