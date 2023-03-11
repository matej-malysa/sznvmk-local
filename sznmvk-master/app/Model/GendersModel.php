<?php
declare(strict_types=1);

namespace App\Model;



class GendersModel extends BaseModel
{
    const T_GENDERS = 'genders';

    public function getAllToSelect(): array
    {
        return $this->db->select('*')->from('%n',self::T_GENDERS)->fetchAssoc('id');
    }

    public function getNameById($id): string
    {
        if($id)
        {
            return $this->db->select('name')->from('%n',self::T_GENDERS)->where('id = %i', $id)->fetchSingle();
        }
        else
        {
            return "";
        }
    }

}
