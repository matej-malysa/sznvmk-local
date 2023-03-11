<?php
declare(strict_types=1);

namespace App\Model;


use Dibi\Connection;
use Dibi\Exception;
use Dibi\Row;

class GroupsModel extends BaseModel
{
    const T_APPLICATIONS = 'applications';
    const T_GROUPS = 'groups';
    const T_GROUPS_PARTICIPANTS = 'groups_participants';
    const T_INVITATIONS = 'invitations';
    const T_APPLICATION_ACTION_TYPE = 'application_action_type';


    /** @var ApplicationsModel */
    protected ApplicationsModel $applicationsModel;

    /** @var ParticipantsModel */
    protected ParticipantsModel $participantsModel;

    /** @var LodgingModel */
    protected LodgingModel $lodgingModel;

    public function __construct(Connection $connection, ApplicationsModel $applicationsModel,  ParticipantsModel $participantsModel,  LodgingModel $lodgingModel)
    {
        parent::__construct($connection);
        $this->applicationsModel = $applicationsModel;
        $this->participantsModel = $participantsModel;
        $this->lodgingModel = $lodgingModel;
    }

    /**************************************************GROUPS**********************************************************/

    /**
     * @param int $id
     * @throws Exception
     */
    public function createGroup(int $id): void
    {
        $values['create_by'] = $id;
        $values['count'] = 1;
        $session = $this->applicationsModel->getApplicationRaw($id)['session'];
        if( $this->lodgingModel->IsForBung($id,$session))
        {
            $values['lodge_type'] = 1;
        }
        else
        {
            $values['lodge_type'] = 2;
        }
        $this->db->insert(self::T_GROUPS, $values)->execute();
        $groupId = $this->db->select('grp.id')
            ->from('%n as grp',self::T_GROUPS)
            ->where('grp.create_by = %i',$id)
            ->fetchSingle();
        $new_values['group_id'] = $groupId;
        $new_values['application_id'] = $id;

        $this->db->insert(self::T_GROUPS_PARTICIPANTS, $new_values)->execute();
    }

     /**
     * @param int $groupId
     * @param int $session
     * @throws Exception
     */
    public function RetypeLodgeForGroup(int $groupId,int $session)
    {


        //Retype na zaklade poradia zaplatenia
        $group =   $this->db->select('grp_prt.application_id')

            ->from('%n AS grp_prt', self::T_GROUPS_PARTICIPANTS)
            ->where('grp_prt.group_id = %i',$groupId)
            ->fetchAll();
        $lodge_type = 1;
        foreach ($group as $member)
        {
            if(!$this->lodgingModel->IsForBung($member['application_id'],$session))
            {
                $lodge_type = 2;
                break;
            }

        }

        //retype na zaklade obsadenosti bung

        if($lodge_type == 1 and count($group) > 1)
        {
            $NoFreeBung = true;
            $FreePlaces = $this->lodgingModel->getLodgingForGroups($session,$groupId);
            for($i=0;count($group)+$i<7;$i++)
            {
                if ($FreePlaces['Bung'][count($group)+$i] > 0)
                {
                    $NoFreeBung = false;
                    break;
                }
            }
            if($NoFreeBung)
            {
                $lodge_type = 2;
            }
        }

        $this->db->update(self::T_GROUPS,['lodge_type' => $lodge_type])->where('id = %i',$groupId)->execute();
    }

    /**
     * @param int $id
     * @return Row|null
     */
    public function getGroup(int $id): ?Row
    {
        $out = $this->db->select('grp.id , grp.create_by,grp.count')
        ->from('%n AS grp_prt', self::T_GROUPS_PARTICIPANTS)
        ->leftJoin('%n AS grp ON grp_prt.group_id = grp.id',self::T_GROUPS)
        ->where('grp_prt.application_id = %i',$id)
        ->fetchAll();
        if(isset($out[0]))
        {
            return $out[0];
        }
        else
        {
            return null;
        }

    }

    /**
     * @param int $id
     * @throws Exception
     */
    public function deleteGroup(int $id): void
    {
        $groupId = $this->db->select('grp.id')
            ->from('%n AS grp', self::T_GROUPS)
            ->where('grp.create_by = %i',$id)
            ->fetchSingle();
        $this->db->delete(self::T_GROUPS)->where('id = %i', $groupId)->execute();
        $this->db->delete(self::T_GROUPS_PARTICIPANTS)->where('group_id = %i', $groupId)->execute();
        $this->db->delete(self::T_INVITATIONS)->where('group_id = %i', $groupId)->execute();

    }

    /**
     * @param int $id
     * @throws Exception
     */
    public function deleteFromGroup(int $id): void
    {
        $group = $this->getGroup($id);
        $this->db->update(self::T_GROUPS,['count' => $group['count'] - 1])->where('id = %i',$group['id'])->execute();
        $this->db->delete(self::T_GROUPS_PARTICIPANTS)->where('application_id = %i', $id)->execute();
        $session = $this->db->select('app.session')
            ->from('%n as app',self::T_APPLICATIONS)
            ->where('id = %i', $id)
            ->fetchSingle();
        $this->RetypeLodgeForGroup($group['id'],$session);
    }


    /**
     * @param int $id
     * @return string
     */
    public function getIDforGroup(int $id): string
    {
        return strval($this->db->select('app.vs')
            ->from('%n AS app', self::T_APPLICATIONS)
            ->where('app.id = %i',$id)
            ->fetchSingle());
    }




    /*****************************************************************INVITATIONS*****************************************/

    /**
     * @param int $by
     * @param int $to
     * @return int
     * @throws Exception
     */
    public function createInvitation(int $by ,int $to): int
    {
        $values['send_by'] = $by;

        $session_by = $this->db->select('app.session')
            ->from('%n AS app', self::T_APPLICATIONS)
            ->where('app.id = %i',$by)
            ->fetchSingle();

        if(($values['send_to'] = GroupsModel::code_to_id($to)) == -1)
        {
            return -1;
        }
        elseif($values['send_to'] == -2)
        {
            return -2;
        }
        else if ($values['send_to'] == $by)
        {
            return -3;
        }
        else if($this->isInvited($values['send_by'],$values['send_to']))
        {
            return -4;
        }
        else if($session_by != $this->db->select('app.session')
                ->from('%n AS app', self::T_APPLICATIONS)
                ->where('app.id = %i',$values['send_to'])
                ->fetchSingle())
        {
            return -5;
        }
        $values['group_id'] = $this->db->select('grp.id')
            ->from('%n AS grp', self::T_GROUPS)
            ->where('grp.create_by = %i',$by)
            ->fetchSingle();
        $this->db->insert(self::T_INVITATIONS, $values)->execute();
        return 1;
    }

   /**
     * @param int $by
     * @param int $to
     * @return int
     */
    public function isInvited(int $by,int $to): int
    {
        return count($this->db->select('inv.id')
            ->from('%n AS inv', self::T_INVITATIONS)
            ->where('inv.send_by = %i and inv.send_to = %i',$by,$to)
            ->fetchAll());
    }

   /**
     * @param array $FreePlaces
     * @param array $Invitations
     * @param int $InviOut
     * @param array $BungGroups
     * @param array $HutGroups
     * @return array
     */
    public function setOtherInviToFreePlaces(array $FreePlaces, array $Invitations,int $InviOut, array $BungGroups, array $HutGroups): array
    {

        unset($Invitations[$InviOut]);
        foreach ($Invitations as $key=>$invi)
        {
            if(isset($BungGroups[$invi['group_id']]))
            {
                $group = $BungGroups[$invi['group_id']];
                for($i=0;$group['count']+1+$i<7;$i++)
                {
                    if ($FreePlaces['Bung'][$group['count']+1 + $i] > 0)
                    {
                        $FreePlaces['Bung'][$group['count'] + 1 + $i] = $FreePlaces['Bung'][$group['count'] + 1 + $i] - 1;
                        if($i>1)
                        {
                            $FreePlaces['Bung'][$i] = $FreePlaces['Bung'][$i]  + 1;
                        }
                        break;
                    }
                }
            }
            else
            {
                $group = $HutGroups[$invi['group_id']];
                for($i=0;$group['count']+1+$i<7;$i++)
                {
                    if ($FreePlaces['Hut'][$group['count'] +1+ $i] > 0)
                    {
                        $FreePlaces['Hut'][$group['count'] +1+ $i] = $FreePlaces['Hut'][$group['count'] +1+ $i] - 1;
                        if($i>1)
                        {
                            $FreePlaces['Hut'][$i] = $FreePlaces['Hut'][$i]  + 1;
                        }
                        break;
                    }
                }
            }
        }
        return $FreePlaces;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getInvitation(int $id): array
    {
        $session = $this->db->select('app.session')
            ->from('%n AS app', self::T_APPLICATIONS)
            ->where('app.id = %i',$id)
            ->fetchSingle();

        $FreePlaces =  $this->lodgingModel->getLodgingForGroups($session, inv_send_to: $id);


        $Bung_groups = $this->db->select('grp.id,grp.count')
            ->from('%n as grp', GroupsModel::T_GROUPS)
            ->where('lodge_type = 1')
            ->orderBy('grp.count')
            ->fetchAssoc('id');

        $Hut_groups = $this->db->select('grp.id,grp.count')
            ->from('%n as grp', GroupsModel::T_GROUPS)
            ->where('lodge_type = 2')
            ->orderBy('grp.count')
            ->fetchAssoc('id');

        $IsForBung = $this->lodgingModel->IsForBung($id,$session);


        $Invitations = $this->db->select('inv.id, app.firstname, app.lastname, inv.send_by , inv.send_to, inv.group_id, 0 as CanAccept')
            ->from('%n AS inv', self::T_INVITATIONS)
            ->leftJoin('%n AS app ON app.id = inv.send_by',self::T_APPLICATIONS)
            ->where('inv.send_to = %i',$id)
            ->fetchAll();

        $CopyFreePlaces = $FreePlaces;

        foreach ($Invitations as $key=>$invi)
        {
            $FreePlaces = $this->setOtherInviToFreePlaces($CopyFreePlaces,$Invitations,$key,$Bung_groups,$Hut_groups);

            if($IsForBung and isset($Bung_groups[$invi['group_id']]))
            {
                for($i=0;$Bung_groups[$invi['group_id']]['count'] + 1 +$i<7;$i++)
                {
                    if ($FreePlaces['Bung'][$Bung_groups[$invi['group_id']]['count'] + 1 + $i] > 0)
                    {
                        $Invitations[$key]['CanAccept'] = 1;
                        break;
                    }
                }
                if(!$Invitations[$key]['CanAccept'])
                {
                    for ($i = 0; $Bung_groups[$invi['group_id']]['count'] + 1 + $i < 7; $i++)
                    {
                        if ($FreePlaces['Hut'][$Bung_groups[$invi['group_id']]['count'] + 1 + $i] > 0)
                        {
                            $Invitations[$key]['CanAccept'] = 1;
                            break;
                        }
                    }
                }
            }
            else
            {
                if(isset($Bung_groups[$invi['group_id']]))
                {
                    for ($i = 0; $Bung_groups[$invi['group_id']]['count'] + 1 + $i < 7; $i++)
                    {
                        if ($FreePlaces['Hut'][$Bung_groups[$invi['group_id']]['count'] + 1 + $i] > 0)
                        {
                            $Invitations[$key]['CanAccept'] = 1;
                            break;
                        }
                    }
                }
                else
                {
                    for ($i = 0; $Hut_groups[$invi['group_id']]['count'] + 1 + $i < 7; $i++) {
                        if ($FreePlaces['Hut'][$Hut_groups[$invi['group_id']]['count'] + 1 + $i] > 0) {
                            $Invitations[$key]['CanAccept'] = 1;
                            break;
                        }
                    }
                }
            }
        }

        return $Invitations;
    }

    /**
     * @param int $id
     * @return void
     * @throws Exception
     */
    public function acceptInvitation(int $id): void
    {
        $inv = $this->db->select('inv.id, inv.send_by , inv.send_to, inv.group_id')
            ->from('%n AS inv', self::T_INVITATIONS)
            ->where('inv.id = %i',$id)
            ->fetch();


        $values['group_id'] = $inv['group_id'];

        $values['application_id'] = $inv['send_to'];

        $session = $this->db->select('app.session')
            ->from('%n AS app', self::T_APPLICATIONS)
            ->where('app.id = %i',$inv['send_to'])
            ->fetchSingle();

        $FreePlaces =  $this->lodgingModel->getLodgingForGroups($session);

        $Bung_groups = $this->db->select('grp.id,grp.count')
            ->from('%n as grp', GroupsModel::T_GROUPS)
            ->where('lodge_type = 1')
            ->orderBy('grp.count')
            ->fetchAssoc('id');

        $Hut_groups = $this->db->select('grp.id,grp.count')
            ->from('%n as grp', GroupsModel::T_GROUPS)
            ->where('lodge_type = 2')
            ->orderBy('grp.count')
            ->fetchAssoc('id');

        $IsForBung = $this->lodgingModel->IsForBung($values['application_id'],$session);

        $CanAccept = false;
        $lodge_type = false;

        if($IsForBung and isset($Bung_groups[$inv['group_id']]))
        {
            for($i=0;$Bung_groups[$inv['group_id']]['count'] + 1 +$i<7;$i++)
            {
                if ($FreePlaces['Bung'][$Bung_groups[$inv['group_id']]['count'] + 1 + $i] > 0)
                {
                    $CanAccept = true;
                    $lodge_type = 1;

                }
            }
            if(!$CanAccept)
            {
                for ($i = 0; $Bung_groups[$inv['group_id']]['count'] + 1 + $i < 7; $i++) {
                    if ($FreePlaces['Hut'][$Bung_groups[$inv['group_id']]['count'] + 1 + $i] > 0) {
                        $CanAccept = true;
                        $lodge_type = 2;
                    }
                }
            }
        }
        else
        {
            if(isset($Bung_groups[$inv['group_id']]))
            {
                for ($i = 0; $Bung_groups[$inv['group_id']]['count'] + 1 + $i < 7; $i++)
                {
                    if ($FreePlaces['Hut'][$Bung_groups[$inv['group_id']]['count'] + 1 + $i] > 0)
                    {
                        $CanAccept = true;
                        $lodge_type = 2;
                    }
                }
            }
            else
            {
                for ($i = 0; $Hut_groups[$inv['group_id']]['count'] + 1 + $i < 7; $i++)
                {
                    if ($FreePlaces['Hut'][$Hut_groups[$inv['group_id']]['count'] + 1 + $i] > 0)
                    {
                        $CanAccept = true;
                        $lodge_type = 2;
                    }
                }
            }
        }




        if(($this->db->select('grp_prt.id')
            ->from('%n AS grp_prt', self::T_GROUPS_PARTICIPANTS)
            ->where('grp_prt.application_id = %i',$inv['send_to'])
            ->fetchSingle() == null)
            and
            $CanAccept)
        {
            $this->db->insert(self::T_GROUPS_PARTICIPANTS, $values)->execute();
            $this->db->update(self::T_GROUPS,['lodge_type' => $lodge_type])->where('id = %i',$values['group_id'])->execute();
            $count = $this->db->select('grp.count')->from('%n as grp',self::T_GROUPS)->where('grp.id = %i',$values['group_id'])->fetchSingle();
            $this->db->update(self::T_GROUPS,['count' => $count + 1])->where('id = %i',$values['group_id'])->execute();
            $this->deniedInvitation($id);
        }
    }

    /**
     * @param int $id
     * @return void
     */
    public function deniedInvitation(int $id): void
    {
        $this->db->delete(self::T_INVITATIONS)->where('id = %i',$id)->execute();
    }




    /******************************************MEMBERS*********************************************************************/

    /**
     * @param int $id
     * @return int
     */
    public function code_to_id(int $id): int
    {
        $val = $this->db->select('app.id')
            ->from('%n AS app', self::T_APPLICATIONS)
            ->where('app.vs = %i',$id)
            ->fetchSingle();
        if($val == null)
        {
            return -1;
        }
        else
        {
            if($this->db->select('par.status')
                ->from('%n AS par', ParticipantsModel::T_PARTICIPANTS)
                ->where('par.application_id = %i',$val)
                ->fetchSingle() == 3)
            {
                return $val;
            }
            else
            {
                return -2;
            }
        }
    }



    /**
     * @param int $id
     * @return string
     */
    public function getNameofCreateBy(int $id): string
    {
        $createById =  $this->db->select('grp.create_by')
            ->from('%n AS grp', self::T_GROUPS)
            ->leftJoin('%n AS grp_prt ON grp_prt.group_id = grp.id',self::T_GROUPS_PARTICIPANTS)
            ->where('grp_prt.application_id = %i',$id)
            ->fetchSingle();
        return $this->db->select('app.firstname')
            ->from('%n AS app', self::T_APPLICATIONS)
            ->where('app.id = %i',$createById)
            ->fetchSingle()
            ." ".
            $this->db->select('app.lastname')
            ->from('%n AS app', self::T_APPLICATIONS)
            ->where('app.id = %i',$createById)
            ->fetchSingle();
    }


    /**
     * @param int $id
     * @return array
     */
    public function getMembers(int $id): array
    {

        $groupId =   $this->db->select('grp.id')
            ->from('%n AS grp', self::T_GROUPS)
            ->leftJoin('%n AS grp_prt ON grp_prt.group_id = grp.id',self::T_GROUPS_PARTICIPANTS)
            ->where('grp_prt.application_id = %i',$id)
            ->groupBy('grp.id')
            ->fetchSingle();
        $groupNames = array();

        $createById =  $this->db->select('grp.create_by')
            ->from('%n AS grp', self::T_GROUPS)
            ->leftJoin('%n AS grp_prt ON grp_prt.group_id = grp.id',self::T_GROUPS_PARTICIPANTS)
            ->where('grp_prt.application_id = %i',$id)
            ->fetchSingle();
        $groupMembersId = $this->db->select('grp_prt.application_id')
            ->from('%n AS grp_prt', self::T_GROUPS_PARTICIPANTS)
            ->where('grp_prt.group_id = %i and grp_prt.application_id != %i',$groupId,$createById)
            ->fetchAll();
        $groupNames[0]['name'] = $this->db->select('app.firstname')
            ->from('%n AS app', self::T_APPLICATIONS)
            ->where('app.id = %i',$createById)
            ->fetchSingle()
        ." ".
        $this->db->select('app.lastname')
            ->from('%n AS app', self::T_APPLICATIONS)
            ->where('app.id = %i',$createById)
            ->fetchSingle();
        $groupNames[0]['role'] = "Vedúci";
        foreach ($groupMembersId as $key => $memberId)
        {
            $groupNames[$key + 1]['name'] = $this->db->select('app.firstname')
                    ->from('%n AS app', self::T_APPLICATIONS)
                    ->where('app.id = %i',$memberId)
                    ->fetchSingle()
                ." ".
                $this->db->select('app.lastname')
                    ->from('%n AS app', self::T_APPLICATIONS)
                    ->where('app.id = %i',$memberId)
                    ->fetchSingle();
            $groupNames[$key + 1]['role'] = "Člen";
            $groupNames[$key + 1]['id'] = $memberId['application_id'];
        }


        return $groupNames;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getInvitedMembers(int $id): array
    {


        $invitedNames = array();


        $invitedMembersId = $this->db->select('inv.id,inv.send_to')
            ->from('%n AS inv', self::T_INVITATIONS)
            ->where('inv.send_by = %i',$id)
            ->fetchAll();

        foreach ($invitedMembersId as $key => $memberId)
        {
            $invitedNames[$key]['name'] = $this->db->select('app.firstname')
                    ->from('%n AS app', self::T_APPLICATIONS)
                    ->where('app.id = %i',$memberId['send_to'])
                    ->fetchSingle()
                ." ".
                $this->db->select('app.lastname')
                    ->from('%n AS app', self::T_APPLICATIONS)
                    ->where('app.id = %i',$memberId['send_to'])
                    ->fetchSingle();
            $invitedNames[$key]['id'] = $memberId['id'];
        }


        return $invitedNames;
    }



}
