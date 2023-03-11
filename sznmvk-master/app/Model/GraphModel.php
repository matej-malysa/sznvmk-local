<?php
declare(strict_types=1);

namespace App\Model;

use Nette\Utils\DateTime;

class GraphModel extends BaseModel
{
    const T_GRAPH = 'graph_';
    

    const NW_ZAD_INS = 'Zadáno instruktorem';
    const NW_ZAD_UCS = 'Zadáno účastníkem';
    const NW_UCASTNIK = 'Účastník';
    const NW_SMAZANY = 'Smazáno';

    const NW_ZAJEMCE = 'Zájemce';
    const NW_GUEST = 'Guest';
    const NW_POTVRZENY = 'Potvrzený';
    const NW_WAIT = 'Waiting for action';

    const COL_STATUS = 'Status';


    /**
     * @return array
     */
    public function getArrayofYears(): array
    {
        $Today_year = intval((new DateTime())->setTimestamp((new DateTime())->getTimestamp())->format('Y'));
        $data = array();
        for($i =  $Today_year - 1; $i > $Today_year - 5 ;$i-- )
        {
            $data[] = $i;
        }
        return $data;
    }



    public function setColumn($data,$type,$fac): array
    {
        bdump($fac);

        $year=2021;

        if($type == 1)
        {
            $act = 'Applications_';
        }
        elseif($type == 2)
        {
            $act = 'First_deposit_';
        }
        else
        {
            $act = 'Fully_paid_';
        }

        foreach ($data[0] as $key=>$nieco)
        {
            if($fac == 'Bez školy')
            {
                $this->db->update(self::T_GRAPH.'2021',['date' => $nieco , $act.'BEZ_SKOLY' => $data[1][$key]])
                    ->where('date = ?', $nieco)
                    ->execute();
            }
            else
            $this->db->update(self::T_GRAPH.'2021',['date' => $nieco , $act.$fac => $data[1][$key]])
                ->where('date = ?', $nieco)
                ->execute();
        }

        return [];
    }

    /**
     * @return array
     */
    public function getAppLogStartEndDateThisYear(): array
    {
        return $this->db->select('app.*')
            ->from('%n', ApplicationsModel::T_APPLICATION_LOG)->as('app')
            ->where('app.action_type = %i OR app.action_type = %i OR 
                           app.action_type = %i OR app.action_type = %i'
                           ,ApplicationsModel::ACTION_CREATE,ApplicationsModel::ACTION_DELETE,ApplicationsModel::ACTION_EDIT,ApplicationsModel::ACTION_EDIT_PARTICIPANT)
            ->orderBy('app.created_at')
            ->fetchAll();
    }

    /**
     * @return array
     */
    public function getDepStartEndDateThisYear(): array
    {
        return $this->db->select('app.* ')
            ->from('%n', ApplicationsModel::T_APPLICATION_LOG)->as('app')
            ->where('app.action_type = %i OR app.action_type = %i OR 
                           app.action_type = %i OR app.action_type = %i'
                ,ApplicationsModel::ACTION_CREATE,ApplicationsModel::ACTION_DELETE,ApplicationsModel::ACTION_EDIT,ApplicationsModel::ACTION_EDIT_PARTICIPANT)
            ->orderBy('created_at')
            ->fetchAll();
    }

    /**
     * @return array
     */
    public function getPaidStartEndDateThisYear(): array
    {
        return $this->db->select('app.*')
            ->from('%n', ApplicationsModel::T_APPLICATION_LOG)->as('app')
            ->where('app.action_type = %i OR app.action_type = %i OR 
                           app.action_type = %i OR app.action_type = %i'
                ,ApplicationsModel::ACTION_CREATE,ApplicationsModel::ACTION_DELETE,ApplicationsModel::ACTION_EDIT,ApplicationsModel::ACTION_EDIT_PARTICIPANT)
            ->orderBy('created_at')
            ->fetchAll();
    }

    /**
     * @return array
     */
    public function getAppStartEndDateBeforeThisYear($array_of_years): array
    {
        $start = $this->db->select('app.date')
        ->from('%n', self::T_GRAPH.$array_of_years[0])->as('app')
        ->where('app.Applications_All != "NULL"')
        ->orderBy('app.date')
        ->limit(1)
        ->fetchSingle();

        $end  = $this->db->select('app.date')
        ->from('%n', self::T_GRAPH.$array_of_years[0])->as('app')
        ->where('app.Applications_All != "NULL"')
        ->orderBy('app.date DESC')
        ->limit(1)
        ->fetchSingle();


        foreach($array_of_years as $year)
        {
            $start1 = $this->db->select('app.date')
                ->from('%n', self::T_GRAPH.$year)->as('app')
                ->where('app.Applications_All != "NULL"')
                ->orderBy('app.date')
                ->limit(1)
                ->where('app.date < ?',$start)
                ->fetchSingle();
            if($start1 != null)
            {
                $start = $start1;
            }
        }
        foreach($array_of_years as $year)
        {
            $end1 = $this->db->select('app.date')
                ->from('%n', self::T_GRAPH.$year)->as('app')
                ->where('app.Applications_All != "NULL"')
                ->orderBy('app.date DESC')
                ->limit(1)
                ->where('app.date > ?',$end)
                ->fetchSingle();

            if($end1 != null)
            {
                $end = $end1;
            }
        }
        return [$start,$end];
    }

    /**
     * @return array
     */
    public function getDepStartDateBeforeThisYear($array_of_years): array
    {

        $start = $this->db->select('app.date')
            ->from('%n', self::T_GRAPH.$array_of_years[0])->as('app')
            ->where('app.First_deposit_All != "NULL"')
            ->orderBy('app.date')
            ->limit(1)
            ->fetchSingle();
        $end  = $this->db->select('app.date')
            ->from('%n', self::T_GRAPH.$array_of_years[0])->as('app')
            ->where('app.First_deposit_All != "NULL"')
            ->orderBy('app.date DESC')
            ->limit(1)
            ->fetchSingle();


        foreach($array_of_years as $year)
        {
            $start1 = $this->db->select('app.date')
                ->from('%n', self::T_GRAPH.$year)->as('app')
                ->where('app.First_deposit_All != "NULL"')
                ->orderBy('app.date')
                ->limit(1)
                ->where('app.date < ?',$start)
                ->fetchSingle();
            if($start1 != null)
            {
                $start = $start1;
            }
        }
        foreach($array_of_years as $year)
        {
            $end1 = $this->db->select('app.date')
                ->from('%n', self::T_GRAPH.$year)->as('app')
                ->where('app.First_deposit_All != "NULL"')
                ->orderBy('app.date DESC')
                ->limit(1)
                ->where('app.date > ?',$end)
                ->fetchSingle();

            if($end1 != null)
            {
                $end = $end1;
            }
        }

        return [$start,$end];
    }

    /**
     * @return array
     */
    public function getPaidStartDateBeforeThisYear($array_of_years): array
    {
        $start = $this->db->select('app.date')
            ->from('%n', self::T_GRAPH.$array_of_years[0])->as('app')
            ->where('app.Fully_paid_All != "NULL"')
            ->orderBy('app.date')
            ->limit(1)
            ->fetchSingle();
        $end  = $this->db->select('app.date')
            ->from('%n', self::T_GRAPH.$array_of_years[0])->as('app')
            ->where('app.Fully_paid_All != "NULL"')
            ->orderBy('app.date DESC')
            ->limit(1)
            ->fetchSingle();


        foreach($array_of_years as $year)
        {
            $start1 = $this->db->select('app.date')
                ->from('%n', self::T_GRAPH.$year)->as('app')
                ->where('app.Fully_paid_All != "NULL"')
                ->orderBy('app.date')
                ->limit(1)
                ->where('app.date < ?',$start)
                ->fetchSingle();
            if($start1 != null)
            {
                $start = $start1;
            }
        }
        foreach($array_of_years as $year)
        {
            $end1 = $this->db->select('app.date')
                ->from('%n', self::T_GRAPH.$year)->as('app')
                ->where('app.Fully_paid_All != "NULL"')
                ->orderBy('app.date DESC')
                ->limit(1)
                ->where('app.date > ?',$end)
                ->fetchSingle();

            if($end1 != null)
            {
                $end = $end1;
            }
        }

        return [$start,$end];
    }

    public function getAppDayCountThisYear($start, $end, $fac): int
    {
        $Today_year = (new DateTime())->setTimestamp((new DateTime())->getTimestamp())->format('Y');
        if ($fac == 'All') {
            $create = $this->db->select('app.*')
                ->from('%n', ApplicationsModel::T_APPLICATION_LOG)->as('app')
                ->where('((app.action_type = %i) OR (app.action_type = %i AND app.old_value = ? AND app.new_value != ?))'
                    ,ApplicationsModel::ACTION_CREATE,ApplicationsModel::ACTION_EDIT, self::NW_SMAZANY, self::NW_SMAZANY)
                ->where('app.created_at >=  ?', (new DateTime())->setTimestamp($start)->format($Today_year.'-m-d 00:00:00'))
                ->where('app.created_at <=  ?', (new DateTime())->setTimestamp($end)->format($Today_year.'-m-d 23:59:59'))
                ->count();

            $delete = $this->db->select('app.*')
                ->from('%n', ApplicationsModel::T_APPLICATION_LOG)->as('app')
                ->where('app.action_type = %i', ApplicationsModel::ACTION_DELETE)
                ->where('app.created_at >=  ?', (new DateTime())->setTimestamp($start)->format($Today_year.'-m-d 00:00:00'))
                ->where('app.created_at <=  ?', (new DateTime())->setTimestamp($end)->format($Today_year.'-m-d 23:59:59'))
                ->count();
        } elseif ($fac == 'Bez školy') {
            $create = $this->db->select('app.*')
                ->from('%n', ApplicationsModel::T_APPLICATION_LOG)->as('app')
                ->where('((app.action_type = %i) OR (app.action_type = %i AND app.old_value = ? AND app.new_value != ?))'
                    ,ApplicationsModel::ACTION_CREATE,ApplicationsModel::ACTION_EDIT, self::NW_SMAZANY, self::NW_SMAZANY)
                ->where('app.created_at >=  ?', (new DateTime())->setTimestamp($start)->format($Today_year.'-m-d 00:00:00'))
                ->where('app.created_at <=  ?', (new DateTime())->setTimestamp($end)->format($Today_year.'-m-d 23:59:59'))
                ->leftJoin('%n AS realapp ON app.application_id = realapp.id', ApplicationsModel::T_APPLICATIONS)
                ->where('realapp.faculty = %i', FacultiesModel::NO_SCHOOL_ID)
                ->count();

            $delete = $this->db->select('app.*')
                ->from('%n', ApplicationsModel::T_APPLICATION_LOG)->as('app')
                ->where('app.action_type = %i', ApplicationsModel::ACTION_DELETE)
                ->where('app.created_at >=  ?', (new DateTime())->setTimestamp($start)->format($Today_year.'-m-d 00:00:00'))
                ->where('app.created_at <=  ?', (new DateTime())->setTimestamp($end)->format($Today_year.'-m-d 23:59:59'))
                ->leftJoin('%n AS realapp ON app.application_id = realapp.id', ApplicationsModel::T_APPLICATIONS)
                ->where('realapp.faculty = %i', FacultiesModel::NO_SCHOOL_ID)
                ->count();
        } else {
            $create = $this->db->select('app.*')
                ->from('%n', ApplicationsModel::T_APPLICATION_LOG)->as('app')
                ->where('((app.action_type = %i) OR (app.action_type = %i AND app.old_value = ? AND app.new_value != ?))'
                    ,ApplicationsModel::ACTION_CREATE,ApplicationsModel::ACTION_EDIT, self::NW_SMAZANY, self::NW_SMAZANY)
                ->where('app.created_at >=  ?', (new DateTime())->setTimestamp($start)->format($Today_year.'-m-d 00:00:00'))
                ->where('app.created_at <=  ?', (new DateTime())->setTimestamp($end)->format($Today_year.'-m-d 23:59:59'))
                ->leftJoin('%n AS realapp ON app.application_id = realapp.id', ApplicationsModel::T_APPLICATIONS)
                ->leftJoin('%n AS faculty ON realapp.faculty = faculty.id', FacultiesModel::T_FACULTIES)
                ->where('faculty.code = ?', $fac)
                ->count();

            $delete = $this->db->select('app.*')
                ->from('%n', ApplicationsModel::T_APPLICATION_LOG)->as('app')
                ->where('app.action_type = %i', ApplicationsModel::ACTION_DELETE)
                ->where('app.created_at >=  ?', (new DateTime())->setTimestamp($start)->format($Today_year.'-m-d 00:00:00'))
                ->where('app.created_at <=  ?', (new DateTime())->setTimestamp($end)->format($Today_year.'-m-d 23:59:59'))
                ->leftJoin('%n AS realapp ON app.application_id = realapp.id', ApplicationsModel::T_APPLICATIONS)
                ->leftJoin('%n AS faculty ON realapp.faculty = faculty.id', FacultiesModel::T_FACULTIES)
                ->where('faculty.code = ?', $fac)
                ->count();

        }
        return $create - $delete;
    }

    public function getDepDayCountThisYear($start, $end, $fac): int
    {
        $Today_year = (new DateTime())->setTimestamp((new DateTime())->getTimestamp())->format('Y');
        if($fac == 'All') {
            $create = $this->db->select('app.*')
                ->from('%n', ApplicationsModel::T_APPLICATION_LOG)->as('app')
                ->where('app.column = ? AND 
                ( app.old_value = ? OR app.old_value = ? OR app.old_value = ? ) AND 
                (app.new_value = ? OR app.new_value = ? OR app.new_value = ? OR app.new_value = ? OR app.new_value = ?)',
                self::COL_STATUS,
                self::NW_ZAD_INS,self::NW_ZAD_UCS,self::NW_SMAZANY,
                self::NW_UCASTNIK,self::NW_ZAJEMCE,self::NW_GUEST,self::NW_WAIT,self::NW_POTVRZENY)
                ->where('app.created_at >=  ?', (new DateTime())->setTimestamp($start)->format($Today_year.'-m-d 00:00:00'))
                ->where('app.created_at <=  ?', (new DateTime())->setTimestamp($end)->format($Today_year.'-m-d 23:59:59'))
                ->count();

            $delete = $this->db->select('app.*')
                ->from('%n', ApplicationsModel::T_APPLICATION_LOG)->as('app')
                ->innerJoin('(SELECT app2.application_id , MIN(app2.created_at) as min_created_at FROM %n as app2 
                                    WHERE app2.action_type = %i AND app2.new_value = ? GROUP BY app2.application_id) as appp 
                                    ON app.application_id = appp.application_id  AND app.created_at >= appp.min_created_at',
                                    ApplicationsModel::T_APPLICATION_LOG,ApplicationsModel::ACTION_EDIT,self::NW_UCASTNIK)
                ->where('((app.action_type = %i) OR 
                (app.column = ? AND 
                (app.old_value = ? OR app.old_value = ? OR app.old_value = ? OR app.old_value = ? OR app.old_value = ?) AND
                 (app.new_value = ? OR app.new_value = ? OR app.new_value = ? )))',
                ApplicationsModel::ACTION_DELETE,
                self::COL_STATUS,
                self::NW_UCASTNIK,self::NW_ZAJEMCE,self::NW_GUEST,self::NW_WAIT,self::NW_POTVRZENY,
                self::NW_ZAD_INS,self::NW_ZAD_UCS,self::NW_SMAZANY)
                ->where('app.created_at >=  ?', (new DateTime())->setTimestamp($start)->format($Today_year.'-m-d 00:00:00'))
                ->where('app.created_at <=  ?', (new DateTime())->setTimestamp($end)->format($Today_year.'-m-d 23:59:59'))
                ->count();
        } else if($fac == 'Bez školy') {
            $create = $this->db->select('app.*')
                ->from('%n', ApplicationsModel::T_APPLICATION_LOG)->as('app')
                ->where('app.column = ? AND 
                ( app.old_value = ? OR app.old_value = ? OR app.old_value = ? ) AND 
                (app.new_value = ? OR app.new_value = ? OR app.new_value = ? OR app.new_value = ? OR app.new_value = ?)',
                    self::COL_STATUS,
                    self::NW_ZAD_INS,self::NW_ZAD_UCS,self::NW_SMAZANY,
                    self::NW_UCASTNIK,self::NW_ZAJEMCE,self::NW_GUEST,self::NW_WAIT,self::NW_POTVRZENY)
                ->where('app.created_at >=  ?', (new DateTime())->setTimestamp($start)->format($Today_year.'-m-d 00:00:00'))
                ->where('app.created_at <=  ?', (new DateTime())->setTimestamp($end)->format($Today_year.'-m-d 23:59:59'))
                ->leftJoin('%n AS realapp ON app.application_id = realapp.id', ApplicationsModel::T_APPLICATIONS)
                ->where('realapp.faculty = %i', FacultiesModel::NO_SCHOOL_ID)
                ->count();

            $delete = $this->db->select('app.*')
                ->from('%n', ApplicationsModel::T_APPLICATION_LOG)->as('app')
                ->innerJoin('(SELECT app2.application_id , MIN(app2.created_at) as min_created_at FROM %n as app2 
                                    WHERE app2.action_type = %i AND app2.new_value = ? GROUP BY app2.application_id) as appp 
                                    ON app.application_id = appp.application_id  AND app.created_at >= appp.min_created_at',
                    ApplicationsModel::T_APPLICATION_LOG,ApplicationsModel::ACTION_EDIT,self::NW_UCASTNIK)
                ->where('((app.action_type = %i) OR 
                (app.column = ? AND 
                (app.old_value = ? OR app.old_value = ? OR app.old_value = ? OR app.old_value = ? OR app.old_value = ?) AND
                 (app.new_value = ? OR app.new_value = ? OR app.new_value = ? )))',
                    ApplicationsModel::ACTION_DELETE,
                    self::COL_STATUS,
                    self::NW_UCASTNIK,self::NW_ZAJEMCE,self::NW_GUEST,self::NW_WAIT,self::NW_POTVRZENY,
                    self::NW_ZAD_INS,self::NW_ZAD_UCS,self::NW_SMAZANY)
                ->where('app.created_at >=  ?', (new DateTime())->setTimestamp($start)->format($Today_year.'-m-d 00:00:00'))
                ->where('app.created_at <=  ?', (new DateTime())->setTimestamp($end)->format($Today_year.'-m-d 23:59:59'))
                ->leftJoin('%n AS realapp ON app.application_id = realapp.id', ApplicationsModel::T_APPLICATIONS)
                ->where('realapp.faculty = %i', FacultiesModel::NO_SCHOOL_ID)
                ->count();
        } else {
            $create = $this->db->select('app.*')
                ->from('%n', ApplicationsModel::T_APPLICATION_LOG)->as('app')
                ->where('app.column = ? AND 
                ( app.old_value = ? OR app.old_value = ? OR app.old_value = ? ) AND 
                (app.new_value = ? OR app.new_value = ? OR app.new_value = ? OR app.new_value = ? OR app.new_value = ?)',
                    self::COL_STATUS,
                    self::NW_ZAD_INS,self::NW_ZAD_UCS,self::NW_SMAZANY,
                    self::NW_UCASTNIK,self::NW_ZAJEMCE,self::NW_GUEST,self::NW_WAIT,self::NW_POTVRZENY)
                ->where('app.created_at >=  ?', (new DateTime())->setTimestamp($start)->format($Today_year.'-m-d 00:00:00'))
                ->where('app.created_at <=  ?', (new DateTime())->setTimestamp($end)->format($Today_year.'-m-d 23:59:59'))
                ->leftJoin('%n AS realapp ON app.application_id = realapp.id', ApplicationsModel::T_APPLICATIONS)
                ->leftJoin('%n AS faculty ON realapp.faculty = faculty.id', FacultiesModel::T_FACULTIES)
                ->where('faculty.code = ?', $fac)
                ->count();

            $delete = $this->db->select('app.*')
                ->from('%n', ApplicationsModel::T_APPLICATION_LOG)->as('app')
                ->innerJoin('(SELECT app2.application_id , MIN(app2.created_at) as min_created_at FROM %n as app2 
                                    WHERE app2.action_type = %i AND app2.new_value = ? GROUP BY app2.application_id) as appp 
                                    ON app.application_id = appp.application_id  AND app.created_at >= appp.min_created_at',
                    ApplicationsModel::T_APPLICATION_LOG,ApplicationsModel::ACTION_EDIT,self::NW_UCASTNIK)
                ->where('((app.action_type = %i) OR 
                (app.column = ? AND 
                (app.old_value = ? OR app.old_value = ? OR app.old_value = ? OR app.old_value = ? OR app.old_value = ?) AND
                 (app.new_value = ? OR app.new_value = ? OR app.new_value = ? )))',
                    ApplicationsModel::ACTION_DELETE,
                    self::COL_STATUS,
                    self::NW_UCASTNIK,self::NW_ZAJEMCE,self::NW_GUEST,self::NW_WAIT,self::NW_POTVRZENY,
                    self::NW_ZAD_INS,self::NW_ZAD_UCS,self::NW_SMAZANY)
                ->where('app.created_at >=  ?', (new DateTime())->setTimestamp($start)->format($Today_year.'-m-d 00:00:00'))
                ->where('app.created_at <=  ?', (new DateTime())->setTimestamp($end)->format($Today_year.'-m-d 23:59:59'))
                ->leftJoin('%n AS realapp ON app.application_id = realapp.id', ApplicationsModel::T_APPLICATIONS)
                ->leftJoin('%n AS faculty ON realapp.faculty = faculty.id', FacultiesModel::T_FACULTIES)
                ->where('faculty.code = ?', $fac)
                ->count();
        }
        return $create - $delete;
    }

    public function getPaidDayCountThisYear($start, $end, $fac): int
    {
        $Today_year = (new DateTime())->setTimestamp((new DateTime())->getTimestamp())->format('Y');
        if($fac == 'All') {
            $create = $this->db->select('app.*')
                ->from('%n', ApplicationsModel::T_APPLICATION_LOG)->as('app')

                ->where('app.column = ? AND 
                ( app.old_value = ? OR app.old_value = ? OR app.old_value = ? OR app.old_value = ? OR app.old_value = ?) AND 
                ( app.new_value = ? OR app.new_value = ? OR app.new_value = ?)',
                    self::COL_STATUS,
                    self::NW_ZAD_INS,self::NW_ZAD_UCS,self::NW_SMAZANY,self::NW_UCASTNIK,self::NW_ZAJEMCE,
                    self::NW_GUEST,self::NW_WAIT,self::NW_POTVRZENY)

                //->where('app.action_type = %i AND app.new_value = ?', ApplicationsModel::ACTION_EDIT_PARTICIPANT, self::NW_POTVRZENY )
                ->where('app.created_at >=  ?', (new DateTime())->setTimestamp($start)->format($Today_year.'-m-d 00:00:00'))
                ->where('app.created_at <=  ?', (new DateTime())->setTimestamp($end)->format($Today_year.'-m-d 23:59:59'))
                ->count();

            $delete = $this->db->select('app.*')
                ->from('%n', ApplicationsModel::T_APPLICATION_LOG)->as('app')
                ->innerJoin('(SELECT app2.application_id , MIN(app2.created_at) as min_created_at FROM %n as app2 
                                    WHERE app2.action_type = %i AND app2.new_value = ? GROUP BY app2.application_id) as appp 
                                    ON app.application_id = appp.application_id  AND app.created_at >= appp.min_created_at',
                    ApplicationsModel::T_APPLICATION_LOG,ApplicationsModel::ACTION_EDIT_PARTICIPANT,self::NW_GUEST)
                ->where('((app.action_type = %i) OR 
                (app.column = ? AND 
                (app.old_value = ? OR app.old_value = ? OR app.old_value = ? ) AND
                (app.new_value = ? OR app.new_value = ? OR app.new_value = ? OR app.new_value = ? OR app.new_value = ?)))',
                    ApplicationsModel::ACTION_DELETE,
                    self::COL_STATUS,
                    self::NW_GUEST,self::NW_WAIT,self::NW_POTVRZENY,
                    self::NW_ZAD_INS,self::NW_ZAD_UCS,self::NW_SMAZANY,self::NW_UCASTNIK,self::NW_ZAJEMCE)
                ->where('app.created_at >=  ?', (new DateTime())->setTimestamp($start)->format($Today_year.'-m-d 00:00:00'))
                ->where('app.created_at <=  ?', (new DateTime())->setTimestamp($end)->format($Today_year.'-m-d 23:59:59'))
                ->count();
        } else if($fac == 'Bez školy') {
            $create = $this->db->select('app.*')
                ->from('%n', ApplicationsModel::T_APPLICATION_LOG)->as('app')
                ->where('app.column = ? AND 
                ( app.old_value = ? OR app.old_value = ? OR app.old_value = ? OR app.old_value = ? OR app.old_value = ?) AND 
                ( app.new_value = ? OR app.new_value = ? OR app.new_value = ?)',
                    self::COL_STATUS,
                    self::NW_ZAD_INS,self::NW_ZAD_UCS,self::NW_SMAZANY,self::NW_UCASTNIK,self::NW_ZAJEMCE,
                    self::NW_GUEST,self::NW_WAIT,self::NW_POTVRZENY)

                ->where('app.created_at >=  ?', (new DateTime())->setTimestamp($start)->format($Today_year.'-m-d 00:00:00'))
                ->where('app.created_at <=  ?', (new DateTime())->setTimestamp($end)->format($Today_year.'-m-d 23:59:59'))
                ->leftJoin('%n AS realapp ON app.application_id = realapp.id', ApplicationsModel::T_APPLICATIONS)
                ->where('realapp.faculty = %i', FacultiesModel::NO_SCHOOL_ID)
                ->count();

            $delete = $this->db->select('app.*')
                ->from('%n', ApplicationsModel::T_APPLICATION_LOG)->as('app')
                ->innerJoin('(SELECT app2.application_id , MIN(app2.created_at) as min_created_at FROM %n as app2 
                                    WHERE app2.action_type = %i AND app2.new_value = ? GROUP BY app2.application_id) as appp 
                                    ON app.application_id = appp.application_id  AND app.created_at >= appp.min_created_at',
                    ApplicationsModel::T_APPLICATION_LOG,ApplicationsModel::ACTION_EDIT_PARTICIPANT,self::NW_GUEST)
                ->where('((app.action_type = %i) OR 
                (app.column = ? AND 
                (app.old_value = ? OR app.old_value = ? OR app.old_value = ? ) AND
                (app.new_value = ? OR app.new_value = ? OR app.new_value = ? OR app.new_value = ? OR app.new_value = ?)))',
                    ApplicationsModel::ACTION_DELETE,
                    self::COL_STATUS,
                    self::NW_GUEST,self::NW_WAIT,self::NW_POTVRZENY,
                    self::NW_ZAD_INS,self::NW_ZAD_UCS,self::NW_SMAZANY,self::NW_UCASTNIK,self::NW_ZAJEMCE)
                ->where('app.created_at >=  ?', (new DateTime())->setTimestamp($start)->format($Today_year.'-m-d 00:00:00'))
                ->where('app.created_at <=  ?', (new DateTime())->setTimestamp($end)->format($Today_year.'-m-d 23:59:59'))
                ->leftJoin('%n AS realapp ON app.application_id = realapp.id', ApplicationsModel::T_APPLICATIONS)
                ->where('realapp.faculty = %i', FacultiesModel::NO_SCHOOL_ID)
                ->count();
        } else {

            $create = $this->db->select('app.*')
                ->from('%n', ApplicationsModel::T_APPLICATION_LOG)->as('app')
                ->where('app.column = ? AND 
                ( app.old_value = ? OR app.old_value = ? OR app.old_value = ? OR app.old_value = ? OR app.old_value = ?) AND 
                ( app.new_value = ? OR app.new_value = ? OR app.new_value = ?)',
                    self::COL_STATUS,
                    self::NW_ZAD_INS,self::NW_ZAD_UCS,self::NW_SMAZANY,self::NW_UCASTNIK,self::NW_ZAJEMCE,
                    self::NW_GUEST,self::NW_WAIT,self::NW_POTVRZENY)

                ->where('app.created_at >=  ?', (new DateTime())->setTimestamp($start)->format($Today_year.'-m-d 00:00:00'))
                ->where('app.created_at <=  ?', (new DateTime())->setTimestamp($end)->format($Today_year.'-m-d 23:59:59'))
                ->leftJoin('%n AS realapp ON app.application_id = realapp.id', ApplicationsModel::T_APPLICATIONS)
                ->leftJoin('%n AS faculty ON realapp.faculty = faculty.id', FacultiesModel::T_FACULTIES)
                ->where('faculty.code = ?', $fac)
                ->count();

            $delete = $this->db->select('app.*')
                ->from('%n', ApplicationsModel::T_APPLICATION_LOG)->as('app')
                ->innerJoin('(SELECT app2.application_id , MIN(app2.created_at) as min_created_at FROM %n as app2 
                                    WHERE app2.action_type = %i AND app2.new_value = ? GROUP BY app2.application_id) as appp 
                                    ON app.application_id = appp.application_id  AND app.created_at >= appp.min_created_at',
                    ApplicationsModel::T_APPLICATION_LOG,ApplicationsModel::ACTION_EDIT_PARTICIPANT,self::NW_GUEST)
                ->where('((app.action_type = %i) OR 
                (app.column = ? AND 
                (app.old_value = ? OR app.old_value = ? OR app.old_value = ? ) AND
                (app.new_value = ? OR app.new_value = ? OR app.new_value = ? OR app.new_value = ? OR app.new_value = ?)))',
                    ApplicationsModel::ACTION_DELETE,
                    self::COL_STATUS,
                    self::NW_GUEST,self::NW_WAIT,self::NW_POTVRZENY,
                    self::NW_ZAD_INS,self::NW_ZAD_UCS,self::NW_SMAZANY,self::NW_UCASTNIK,self::NW_ZAJEMCE)
                ->where('app.created_at >=  ?', (new DateTime())->setTimestamp($start)->format($Today_year.'-m-d 00:00:00'))
                ->where('app.created_at <=  ?', (new DateTime())->setTimestamp($end)->format($Today_year.'-m-d 23:59:59'))
                ->leftJoin('%n AS realapp ON app.application_id = realapp.id', ApplicationsModel::T_APPLICATIONS)
                ->leftJoin('%n AS faculty ON realapp.faculty = faculty.id', FacultiesModel::T_FACULTIES)
                ->where('faculty.code = ?', $fac)
                ->count();

        }
        return $create - $delete;

    }

    public function getAppDayCountBeforeThisYear($year,$fac,$day): ?int
    {
        $data = $this->db->select('app.Applications_'.$fac.' as data' )
            ->from('%n', self::T_GRAPH.$year)->as('app')
            ->where('app.date =  ?',(new DateTime())->setTimestamp($day)->format('1970-m-d'))
            ->fetchSingle();

        return $data;
    }

    public function getDepDayCountBeforeThisYear($year,$fac,$day): ?int
    {
        $data = $this->db->select('app.First_deposit_'.$fac.' as data' )
            ->from('%n', self::T_GRAPH.$year)->as('app')
            ->where('app.date =  ?',(new DateTime())->setTimestamp($day)->format('1970-m-d'))
            ->fetchSingle();

        return $data;
    }

    public function getPaidDayCountBeforeThisYear($year,$fac,$day): ?int
    {
        $data = $this->db->select('app.Fully_paid_'.$fac.' as data' )
            ->from('%n', self::T_GRAPH.$year)->as('app')
            ->where('app.date =  ?',(new DateTime())->setTimestamp($day)->format('1970-m-d'))
            ->fetchSingle();

        return $data;
    }

    public function getAppDailyCountForFacultyBeforeThisYear($graf,$year,$day,$faculty,$storage): ?int
    {
        if ($faculty == 'Bez školy') {
            $faculty = 'BEZ_SKOLY';
        }
        if($graf == 1) {
            $data = $this->getAppDayCountBeforeThisYear($year,$faculty,$day);
        } else if($graf == 2) {
            $data = $this->getDepDayCountBeforeThisYear($year,$faculty,$day);
        } else {
            $data = $this->getPaidDayCountBeforeThisYear($year,$faculty,$day);
        }

        if($data == null ) {
            if( count($storage) == 0) {
                return 0;
            } else {
                return end($storage);
            }
        } else {
            return $data;
        }
    }

    public function getAppAllCount($grafType,$faculty): array
    {
        $array_of_years = $this->getArrayofYears();
        if ($grafType == 1) {
            $date_this_year = $this->getAppLogStartEndDateThisYear();
            $date_before_this_year = $this->getAppStartEndDateBeforeThisYear($array_of_years);
        }
        elseif ($grafType == 2) {
            $date_this_year = $this->getDepStartEndDateThisYear();
            $date_before_this_year = $this->getDepStartDateBeforeThisYear($array_of_years);
        }
        else {
            $date_this_year = $this->getPaidStartEndDateThisYear();
            $date_before_this_year = $this->getPaidStartDateBeforeThisYear($array_of_years);
        }

        $start_time_this_year = DateTime::createFromFormat('Y-m-d H:i:s', (new DateTime())->setTimestamp($date_this_year[0]['created_at']->getTimeStamp())->format('1970-m-d 00:00:00'))->getTimestamp();
        $start_time_before_this_year = DateTime::createFromFormat('Y-m-d H:i:s', (new DateTime())->setTimestamp($date_before_this_year[0]->getTimeStamp())->format('1970-m-d 00:00:00'))->getTimestamp();

        $end_time_this_year = DateTime::createFromFormat('Y-m-d H:i:s', (new DateTime())->setTimestamp($date_this_year[count($date_this_year) -1]['created_at']->getTimeStamp())->format('1970-m-d 23:59:59'))->getTimestamp();
        $end_time_before_this_year = DateTime::createFromFormat('Y-m-d H:i:s', (new DateTime())->setTimestamp($date_before_this_year[count($date_before_this_year) -1]->getTimeStamp())->format('1970-m-d 23:59:59'))->getTimestamp();

        if ($start_time_this_year >  $start_time_before_this_year) {
            $startTime =  $start_time_before_this_year;
        }
        else {
            $startTime = $start_time_this_year;
        }

        if ($end_time_this_year <  $end_time_before_this_year) {
            $endTime =  $end_time_before_this_year;
        }
        else {
            $endTime = $end_time_this_year;
        }
        $from70tothisyear = DateTime::createFromFormat('Y-m-d H:i:s', (new DateTime())->setTimestamp((new DateTime())->getTimeStamp())->format('Y-01-01 00:00:00'))->getTimestamp();
        //$from70to21 = 0;
        $num_of_app_this_year =0;
        $TodayDateTo70 = (new DateTime())->getTimestamp() - $from70tothisyear;
        $Today_year = (new DateTime())->setTimestamp((new DateTime())->getTimestamp())->format('Y');
        $array_of_num[$Today_year] = array();
        foreach ($array_of_years as $year)
        {
            $array_of_num[strval($year)] = array();
        }


        for ( $i = $startTime; $i <= $endTime ; $i = $i + 86400 )
        {
            $shortDate = (new DateTime())->setTimestamp($i)->format('m-d');
            if($end_time_this_year >= $i OR $i <= $TodayDateTo70) {
                if($grafType == 1) {
                    $num_of_app_this_year += $this->getAppDayCountThisYear($i + $from70tothisyear,$i+86399 +$from70tothisyear,$faculty);
                }
                else if($grafType == 2) {
                    $num_of_app_this_year += $this->getDepDayCountThisYear($i + $from70tothisyear,$i+86399 +$from70tothisyear,$faculty);
                }
                else {
                    $num_of_app_this_year += $this->getPaidDayCountThisYear($i + $from70tothisyear,$i+86399 +$from70tothisyear,$faculty);
                }
                $array_of_num[$Today_year][$shortDate] = $num_of_app_this_year;
            }
            foreach ($array_of_years as $year)
            {
                $array_of_num[strval($year)][$shortDate] = $this->getAppDailyCountForFacultyBeforeThisYear($grafType, strval($year), $i, $faculty, $array_of_num[strval($year)]);
            }
        }

        return $array_of_num;
    }
}
