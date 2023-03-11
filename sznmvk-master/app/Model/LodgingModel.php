<?php
declare(strict_types=1);

namespace App\Model;

use App\Components\AppComponent;
use Dibi\Connection;
use Dibi\Exception;
use Dibi\Fluent;
use Dibi\Row;
use Nette\Schema\Elements\Structure;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Utils\ArrayHash;

class LodgingModel extends BaseModel
{
    const T_LODGING = 'lodging';
    const T_LODGING_ACTIVE = 'lodging_active';
    const T_LODGING_TYPE = 'lodging_type';
    const T_LODGING_USE = 'lodging_use';

    const FOR_PARTICIPANTS = 1;

    /** @var Structure|Schema */
    protected Schema|Structure $LodgingSchema;

    protected FacultiesModel $facultiesModel;

    public function __construct(Connection $connection, FacultiesModel $facultiesModel)
    {
        parent::__construct($connection);

        $this->facultiesModel = $facultiesModel;
        $this->LodgingSchema = Expect::structure([
            'name' => Expect::string()->required(),
            'type' => Expect::int()->required(),
            'capacity' => Expect::int()->required(),
            'session_1' => Expect::int()->required(),
            'session_1_use' => Expect::anyOf(null,Expect::int()),
            'session_2' => Expect::int()->required(),
            'session_2_use' => Expect::anyOf(null,Expect::int()),
        ])->castTo(ArrayHash::class);
    }

    public function addLodging(ArrayHash $values)
    {
        $values = $this->validate($this->LodgingSchema, $values);
        $this->db->insert(self::T_LODGING, $values)->execute();
    }

    public function editLodging(int $id,ArrayHash $values)
    {
        if ($values['session_1'] == 0) {
            $values['session_1_use'] = null;
        }

        if ($values['session_2'] == 0) {
            $values['session_2_use'] = null;
        }

        $values = $this->validate($this->LodgingSchema, $values);
        $this->db->update(self::T_LODGING, $values)->where('id = %i', $id)->execute();

    }


    /*********************************************************** GET FUNKCIE ****************************************************/

    public function getCountofBeds(): array
    {
        $B_1_a = $this->db->select("SUM(capacity) as pocet")->from(LodgingModel::T_LODGING)->where('session_1 = 1 and session_1_use = 1 and type = 1')->groupBy('session_1')->fetchSingle();
        $B_1_s = $this->db->select('first_lodging_capacity as pocet')->from(SessionsModel::T_SESSIONS)->where('title = 1')->fetchSingle();
        $B_2_a = $this->db->select("SUM(capacity) as pocet")->from(LodgingModel::T_LODGING)->where('session_2 = 1 and session_2_use = 1 and type = 1')->groupBy('session_2')->fetchSingle();
        $B_2_s = $this->db->select('first_lodging_capacity as pocet')->from(SessionsModel::T_SESSIONS)->where('title = 2')->fetchSingle();
        $CH_1_a = $this->db->select("SUM(capacity) as pocet")->from(LodgingModel::T_LODGING)->where('session_1 = 1 and session_1_use = 1 and type = 2')->groupBy('session_1')->fetchSingle();
        $CH_1_s = $this->db->select('second_lodging_capacity as pocet')->from(SessionsModel::T_SESSIONS)->where('title = 1')->fetchSingle();
        $CH_2_a = $this->db->select("SUM(capacity) as pocet")->from(LodgingModel::T_LODGING)->where('session_2 = 1 and session_2_use = 1 and type = 2')->groupBy('session_2')->fetchSingle();
        $CH_2_s = $this->db->select('second_lodging_capacity as pocet')->from(SessionsModel::T_SESSIONS)->where('title = 2')->fetchSingle();

        return array(intval($B_1_a),$B_1_s,intval($CH_1_a),$CH_1_s,intval($B_2_a),$B_2_s,intval($CH_2_a),$CH_2_s);

    }
    /** @return array */
    public function getAllToSelect(): array
    {
        return $this->db->select('*')->from('%n', self::T_LODGING)->fetchAll();
    }

    /** @return Fluent */
    public function getGrid(): Fluent
    {
        return $this->db->select('lod.id,lod.name,type.name as type,lod.capacity,lod.session_1,use1.name as session_1_use,lod.session_2,use2.name as session_2_use')
            ->from('%n', self::T_LODGING)
            ->as('lod')
            ->leftJoin(self::T_LODGING_TYPE)
            ->as('type')
            ->on('type.id = lod.type')
            ->leftJoin(self::T_LODGING_USE)
            ->as('use1')
            ->on('use1.id = lod.session_1_use')
            ->leftJoin(self::T_LODGING_USE)
            ->as('use2')
            ->on('use2.id = lod.session_2_use')
            ;
    }

    /** @return ?Row */
    public function getLodgingById(int $id): ?Row
    {
        return $this->db->select('lod.*,
                                        (SELECT COUNT(*) as cnt
                                        FROM %n as par
                                        LEFT JOIN %n as app
                                        ON app.id = par.application_id 
                                        WHERE app.session = 1 AND par.room = lod.id
                                        GROUP BY par.room) as cnt1,
                                        (SELECT COUNT(*) as cnt
                                        FROM %n as par
                                        LEFT JOIN %n as app
                                        ON app.id = par.application_id 
                                        WHERE app.session = 2 AND par.room = lod.id
                                        GROUP BY par.room) as cnt2',
                                        ParticipantsModel::T_PARTICIPANTS,ApplicationsModel::T_APPLICATIONS,
                                        ParticipantsModel::T_PARTICIPANTS,ApplicationsModel::T_APPLICATIONS
                                )->from('%n', self::T_LODGING)->as('lod')

            ->where('lod.id = %i',$id)->fetch();
    }


    /**
     * @param int $id
     * @return string
     */
    public function getNameById(int $id): string
    {
        return $this->db->select('name')->from('%n', self::T_LODGING)->where('id = %i', $id)->fetchSingle();
    }


    /**
     * @return array
     */
    public function getTypeToSelect(): array
    {
        return $this->db->select('id, name')->from('%n', self::T_LODGING_TYPE)->fetchAssoc('id');
    }

    /**
     * @return array
     */
    public function getTypeToSelect2(): array
    {
        return $this->db->select('id, name as type')->from('%n', self::T_LODGING_TYPE)->fetchPairs();
    }

    /**
     * @return array
     */
    public function getUseToSelect(): array
    {
        return $this->db->select('id, name')->from('%n', self::T_LODGING_USE)->fetchAssoc('id');
    }

    /**
     * @return array
     */
    public function getUseToSelect2(): array
    {
        return $this->db->select('id, name as session_1_use')->from('%n', self::T_LODGING_USE)->fetchPairs();
    }

    /**
     * @return array
     */
    public function getUseToSelect3(): array
    {
        return $this->db->select('id, name as session_2_use')->from('%n', self::T_LODGING_USE)->fetchPairs();
    }

    /**
     * @return array
     */
    public function getCountTypeForPar(int $session, int $type = 0): int
    {
        if($type)
        {
            return intval($this->db->select('lod.id,lod.type,lod.capacity,SUM(lod.capacity) as count')
                ->from(
                    "((SELECT lod.id,lod.type,(lod.capacity - COUNT(*)) as capacity
                FROM %n as lod 
                LEFT JOIN %n as par
                ON par.room = lod.id
                LEFT JOIN %n as app
                ON par.application_id = app.id
                WHERE lod.session_%i = 1 AND lod.session_%i_use = %i and app.session = %i 
                GROUP BY lod.id,lod.type,lod.capacity
                ORDER BY lod.type ASC ,capacity DESC, lod.id)
                UNION 
                (SELECT lod2.id,lod2.type,lod2.capacity 
                FROM %n as lod2 
                LEFT JOIN (SELECT room FROM %n as par
                            LEFT JOIN %n as app
                            ON par.application_id = app.id
                            WHERE app.session = %i) as par2
                ON par2.room = lod2.id
                WHERE lod2.session_%i = 1 AND lod2.session_%i_use = %i and par2.room IS NULL
                ORDER BY lod2.type ASC ,lod2.capacity DESC, lod2.id))",
                    self::T_LODGING,ParticipantsModel::T_PARTICIPANTS,ApplicationsModel::T_APPLICATIONS,
                    $session,$session,self::FOR_PARTICIPANTS,$session+2,
                    self::T_LODGING,ParticipantsModel::T_PARTICIPANTS,ApplicationsModel::T_APPLICATIONS,$session+2,
                    $session,$session,self::FOR_PARTICIPANTS)->as('lod')
                ->where('lod.type = 1')
                ->groupBy('lod.type')->fetchAll()[0]['count']);
        }
        else
        {

            return intval($this->db->select('lod.id,lod.grp,lod.type,lod.capacity,SUM(lod.capacity) as count')
                ->from(
                    "((SELECT lod.id,lod.type,(lod.capacity - COUNT(*)) as capacity,1 as grp
                FROM %n as lod 
                LEFT JOIN %n as par
                ON par.room = lod.id
                LEFT JOIN %n as app
                ON par.application_id = app.id
                WHERE lod.session_%i = 1 AND lod.session_%i_use = %i and app.session = %i 
                GROUP BY lod.id,lod.type,lod.capacity
                ORDER BY lod.type ASC ,capacity DESC, lod.id)
                UNION 
                (SELECT lod2.id,lod2.type,lod2.capacity, 1 as grp
                FROM %n as lod2 
                LEFT JOIN (SELECT room FROM %n as par
                            LEFT JOIN %n as app
                            ON par.application_id = app.id
                            WHERE app.session = %i) as par2
                ON par2.room = lod2.id
                WHERE lod2.session_%i = 1 AND lod2.session_%i_use = %i and par2.room IS NULL
                ORDER BY lod2.type ASC ,lod2.capacity DESC, lod2.id))",
                    self::T_LODGING,ParticipantsModel::T_PARTICIPANTS,ApplicationsModel::T_APPLICATIONS,
                    $session,$session,self::FOR_PARTICIPANTS,$session+2,
                    self::T_LODGING,ParticipantsModel::T_PARTICIPANTS,ApplicationsModel::T_APPLICATIONS,$session+2,
                    $session,$session,self::FOR_PARTICIPANTS)->as('lod')
                ->groupBy('lod.grp')->fetchAll()[0]['count']);

        }
    }

    public function getScriptEnable(): int
    {
        return $this->db->select('*')->from(self::T_LODGING_ACTIVE)->fetchSingle();
    }



    public function getActualLodgingById(int $id): ?Row
    {
        $data = $this->db->select('app.session,par.room,par.lock_room')->from(ApplicationsModel::T_APPLICATIONS)->as('app')
                    ->innerJoin(ParticipantsModel::T_PARTICIPANTS)->as('par')
            ->on('par.application_id = app.id')
            ->where('id = %i',$id)->fetch();

        return $this->db->select("lod.id,lod.name,lodType.name as type,CONCAT( COUNT(*),'/',lod.capacity) as space,%i as lock_room
                                        ",$data['lock_room'])
                          ->from(LodgingModel::T_LODGING)->as('lod')
            ->innerJoin(ParticipantsModel::T_PARTICIPANTS)->as('par')
            ->on('lod.id = par.room')
            ->innerJoin(ApplicationsModel::T_APPLICATIONS)->as('app')
            ->on('app.id = par.application_id AND app.session = %i AND par.room = %i',$data['session'],$data['room'])
            ->leftJoin(LodgingModel::T_LODGING_TYPE)->as('lodType')
            ->on('lodType.id = lod.type')
            ->groupBy('lod.id,lod.name,lodType.name,lod.capacity')->fetch();

    }

    public function getActualMateById(int $id): array
    {
        $data = $this->db->select('app.session,par.room')->from(ApplicationsModel::T_APPLICATIONS)->as('app')
            ->innerJoin(ParticipantsModel::T_PARTICIPANTS)->as('par')
            ->on('par.application_id = app.id')
            ->where('id = %i',$id)->fetch();

        return $this->db->select("app.*,fac.code as fac,gen.name as gen")
            ->from(LodgingModel::T_LODGING)->as('lod')
            ->innerJoin(ParticipantsModel::T_PARTICIPANTS)->as('par')
            ->on('lod.id = par.room')
            ->innerJoin(ApplicationsModel::T_APPLICATIONS)->as('app')
            ->on('app.id = par.application_id AND app.id != %i AND app.session = %i AND par.room = %i',$id,$data['session'],$data['room'])
            ->leftJoin(FacultiesModel::T_FACULTIES)->as('fac')
            ->on('fac.id = app.faculty')
            ->leftJoin(GendersModel::T_GENDERS)->as('gen')
            ->on('gen.id = app.gender')
            ->fetchAll();

    }

    public function getFreeLodgingById(int $id): Fluent
    {
        $data = $this->db->select('app.session,par.room')->from(ApplicationsModel::T_APPLICATIONS)->as('app')
            ->innerJoin(ParticipantsModel::T_PARTICIPANTS)->as('par')
            ->on('par.application_id = app.id')
            ->where('id = %i',$id)->fetch();

                $return =  $this->db->select( "DISTINCT %i as user,lod.id as  mainid,lod.name as mainname,lodType.name as type,CONCAT( IF(cnt.count IS NULL, '0' ,cnt.count),'/',lod.capacity) as space,lod.capacity,
                                        (SELECT CONCAT( app.firstname,' ',app.lastname,' - ',IF(fac.code != '',fac.code,'Bez školy') )
                                        FROM %n as par
                                        LEFT JOIN %n as app
                                        ON app.id = par.application_id 
                                        LEFT JOIN %n as gen
                                        ON gen.id = app.gender
                                        LEFT JOIN %n as fac
                                        ON fac.id = app.faculty
                                        WHERE app.session = %i AND par.room = lod.id
                                        LIMIT 1) as mate1,
                                        (SELECT CONCAT( app.firstname,' ',app.lastname,' -   ',IF(fac.code != '',fac.code,'Bez školy') )
                                        FROM %n as par
                                        LEFT JOIN %n as app
                                        ON app.id = par.application_id 
                                        LEFT JOIN %n as gen
                                        ON gen.id = app.gender
                                        LEFT JOIN %n as fac
                                        ON fac.id = app.faculty
                                        WHERE app.session = %i AND par.room = lod.id
                                        LIMIT 1,1) as mate2,
                                        (SELECT CONCAT( app.firstname,' ',app.lastname,' -   ',IF(fac.code != '',fac.code,'Bez školy') )
                                        FROM %n as par
                                        LEFT JOIN %n as app
                                        ON app.id = par.application_id 
                                        LEFT JOIN %n as gen
                                        ON gen.id = app.gender
                                        LEFT JOIN %n as fac
                                        ON fac.id = app.faculty
                                        WHERE app.session = %i AND par.room = lod.id
                                        LIMIT 2,1) as mate3,
                                        (SELECT CONCAT( app.firstname,' ',app.lastname,' -   ',IF(fac.code != '',fac.code,'Bez školy') )
                                        FROM %n as par
                                        LEFT JOIN %n as app
                                        ON app.id = par.application_id 
                                        LEFT JOIN %n as gen
                                        ON gen.id = app.gender
                                        LEFT JOIN %n as fac
                                        ON fac.id = app.faculty
                                        WHERE app.session = %i AND par.room = lod.id
                                        LIMIT 3,1) as mate4,
                                        (SELECT CONCAT( app.firstname,' ',app.lastname,'   - ',IF(fac.code != '',fac.code,'Bez školy') )
                                        FROM %n as par
                                        LEFT JOIN %n as app
                                        ON app.id = par.application_id 
                                        LEFT JOIN %n as gen
                                        ON gen.id = app.gender
                                        LEFT JOIN %n as fac
                                        ON fac.id = app.faculty
                                        WHERE app.session = %i AND par.room = lod.id
                                        LIMIT 4,1) as mate5,
                                        (SELECT CONCAT( app.firstname,' ',app.lastname,'  -  ',IF(fac.code != '',fac.code,'Bez školy') )
                                        FROM %n as par
                                        LEFT JOIN %n as app
                                        ON app.id = par.application_id 
                                        LEFT JOIN %n as gen
                                        ON gen.id = app.gender
                                        LEFT JOIN %n as fac
                                        ON fac.id = app.faculty
                                        WHERE app.session = %i AND par.room = lod.id
                                        LIMIT 5,1) as mate6",
                                        $id,
                                        ParticipantsModel::T_PARTICIPANTS,ApplicationsModel::T_APPLICATIONS,GendersModel::T_GENDERS,FacultiesModel::T_FACULTIES,$data['session'],
                                        ParticipantsModel::T_PARTICIPANTS,ApplicationsModel::T_APPLICATIONS,GendersModel::T_GENDERS,FacultiesModel::T_FACULTIES,$data['session'],
                                        ParticipantsModel::T_PARTICIPANTS,ApplicationsModel::T_APPLICATIONS,GendersModel::T_GENDERS,FacultiesModel::T_FACULTIES,$data['session'],
                                        ParticipantsModel::T_PARTICIPANTS,ApplicationsModel::T_APPLICATIONS,GendersModel::T_GENDERS,FacultiesModel::T_FACULTIES,$data['session'],
                                        ParticipantsModel::T_PARTICIPANTS,ApplicationsModel::T_APPLICATIONS,GendersModel::T_GENDERS,FacultiesModel::T_FACULTIES,$data['session'],
                                        ParticipantsModel::T_PARTICIPANTS,ApplicationsModel::T_APPLICATIONS,GendersModel::T_GENDERS,FacultiesModel::T_FACULTIES,$data['session'])
            ->from(LodgingModel::T_LODGING)->as('lod')
            ->leftJoin(ParticipantsModel::T_PARTICIPANTS)->as('par')
            ->on('lod.id = par.room ')
            ->leftJoin(ApplicationsModel::T_APPLICATIONS)->as('app')
            ->on('app.id = par.application_id AND app.session = %i',$data['session'])
            ->leftJoin(LodgingModel::T_LODGING_TYPE)->as('lodType')
            ->on('lodType.id = lod.type')
            ->leftJoin("(SELECT par.room,COUNT(*) as count FROM %n as par
                                        LEFT JOIN %n as app
                                        ON app.id = par.application_id 
                                        WHERE app.session = %i
                                        GROUP BY par.room 
                                        )",
                ParticipantsModel::T_PARTICIPANTS,ApplicationsModel::T_APPLICATIONS,$data['session'])
            ->as('cnt')
            ->on('cnt.room = par.room')
            ->where(' (cnt.count < lod.capacity OR cnt.count IS NULL) and lod.session_%i = 1 AND lod.session_%i_use = 1',$data['session']-2,$data['session']-2);

                if(!$data['room']) {
                    return $return;
                }
                else
                {
                    return $return->where('(par.room != %i OR par.room IS NULL)', $data['room']);
                }


    }

    public function getSwapLodgingById(int $id): Fluent
    {
        $data = $this->db->select('app.session,par.room')->from(ApplicationsModel::T_APPLICATIONS)->as('app')
            ->innerJoin(ParticipantsModel::T_PARTICIPANTS)->as('par')
            ->on('par.application_id = app.id')
            ->where('id = %i',$id)->fetch();

        return $this->db->select("DISTINCT %i as user, %i as user_room,lod.id as  mainid,lod.name as mainname,lodType.name as type,CONCAT( cnt.count,'/',lod.capacity) as space,lod.capacity,
                                        app.id as swapid, app.firstname,app.lastname,fac.code as faculty",
            $id,$data['room'])
            ->from(LodgingModel::T_LODGING)->as('lod')
            ->innerJoin(ParticipantsModel::T_PARTICIPANTS)->as('par')
            ->on('lod.id = par.room')
            ->innerJoin(ApplicationsModel::T_APPLICATIONS)->as('app')
            ->on('app.id = par.application_id AND app.session = %i',$data['session'])
            ->leftJoin(LodgingModel::T_LODGING_TYPE)->as('lodType')
            ->on('lodType.id = lod.type')
            ->leftJoin(FacultiesModel::T_FACULTIES)->as('fac')
            ->on('fac.id = app.faculty')
            ->leftJoin("(SELECT par.room,COUNT(*) as count FROM %n as par
                                        LEFT JOIN %n as app
                                        ON app.id = par.application_id 
                                        WHERE app.session = %i
                                        GROUP BY par.room 
                                        )",
                ParticipantsModel::T_PARTICIPANTS,ApplicationsModel::T_APPLICATIONS,$data['session'])
            ->as('cnt')
            ->on('cnt.room = par.room')
            ->where('par.room != %i and par.lock_room = 0', $data['room']);

    }

    /****************************************** UPDATE FUNKCIE ****************************************/

    public function unsetLodging(int $id,int $made_by,int $all = 0, int $session = 0)
    {
        if(!$all){
        $old_id = $this->db->select('par.room')
            ->from(ParticipantsModel::T_PARTICIPANTS)
            ->as('par')
            ->where('par.application_id = %i',$id)->fetchSingle();
        $old_name = $this->getNameById($old_id);

            $this->db->update(ParticipantsModel::T_PARTICIPANTS,['room' => null,'lock_room' => 0])->where('application_id = %i', $id)->execute();
            $this->LogLodgingChange($id,$made_by,$old_name,null);
        }
        else
        {
                if($session)
                {
                    $AllPar = $this->db->select('par.*')
                        ->from('%n as par', ParticipantsModel::T_PARTICIPANTS)
                        ->leftJoin('%n as app on app.id = par.application_id', ApplicationsModel::T_APPLICATIONS)
                        ->where('par.room IS NOT NULL and app.session = %i',$session +2)
                        ->fetchAll();
                }
                else
                {
                    $AllPar = $this->db->select('*')->from(ParticipantsModel::T_PARTICIPANTS)->where('room IS NOT NULL')->fetchAll();
                }
                foreach ($AllPar as $Par)
                {
                    $old_id = $Par['room'];
                    $old_name = $this->getNameById($old_id);

                    $this->db->update(ParticipantsModel::T_PARTICIPANTS,['room' => null,'lock_room' => 0])->where('application_id = %i', $Par['application_id'])->execute();
                    $this->LogLodgingChange($Par['application_id'],$made_by,$old_name,null);
                }
                $this->db->update(self::T_LODGING_ACTIVE,['enable' => 0])->execute();
        }
    }

    public function makeChange(int $userId, int $lodId,int $made_by)
    {
        $old_id = $this->db->select('par.room')
        ->from(ParticipantsModel::T_PARTICIPANTS)
        ->as('par')
        ->where('par.application_id = %i',$userId)->fetchSingle();
        if($old_id)
            $old_name = $this->getNameById($old_id);
        else{
            $old_name = '';
        }

        $this->db->update(ParticipantsModel::T_PARTICIPANTS,['room' => $lodId])
            ->where('application_id = %i',$userId)->execute();
        $this->LogLodgingChange($userId,$made_by,$old_name,$this->getNameById($lodId));

    }

    public function setLock(int $id)
    {
        $Lock = $this->db->select('lock_room')->from(ParticipantsModel::T_PARTICIPANTS)->where('application_id = %i' , $id)->fetchSingle();

        if($Lock)
            $this->db->update(ParticipantsModel::T_PARTICIPANTS,['lock_room' => 0])->where('application_id = %i' , $id)->execute();
        else
            $this->db->update(ParticipantsModel::T_PARTICIPANTS,['lock_room' => 1])->where('application_id = %i' , $id)->execute();

    }

    /****************************************************** LOG **************************************************/

    public function LogLodgingChange(int $userId,int $madeBy, string|null $old_lod, string|null $new_lod)
    {


        $values['application_id'] = $userId;
        $values['made_by'] = $madeBy;
        $values['action_type'] = 8; // Table application_action_type
        if($old_lod == '')
        {
            $values['old_value'] =  null;
        }
        else
        {
            $values['old_value'] =  $old_lod;
        }

        $values['new_value'] =  $new_lod;

        $this->db->insert(ApplicationsModel::T_APPLICATION_LOG, $values)->execute();
    }

    /*********************************************** UBYTOVACI SKRIPT A POMOCNE FUNKCIE **************************************************/

    public function getLodgingForPar(int $session, int $first): array
    {
        if($first)
        {
            return $this->db->select('lod.id,lod.type,lod.capacity')
                ->from( self::T_LODGING)->as('lod')
                ->leftJoin(ParticipantsModel::T_PARTICIPANTS)->as('par')
                ->on('par.room = lod.id')
                ->leftJoin(ApplicationsModel::T_APPLICATIONS)->as('app')
                ->on('par.application_id = app.id ')
                ->where('lod.session_%i = 1 AND lod.session_%i_use = %i and par.room IS NULL',$session,$session,self::FOR_PARTICIPANTS)
                ->orderBy('lod.type ASC ,capacity DESC, lod.id')
                ->fetchAll();
        }
        else
        {
            return $this->db->select('lod.id,lod.type,lod.capacity')
                ->from(
                    "((SELECT lod.id,lod.type,(lod.capacity - COUNT(*)) as capacity
                FROM %n as lod 
                LEFT JOIN %n as par
                ON par.room = lod.id
                LEFT JOIN %n as app
                ON par.application_id = app.id
                WHERE lod.session_%i = 1 AND lod.session_%i_use = %i and app.session = %i 
                GROUP BY lod.id,lod.type,lod.capacity
                ORDER BY lod.type ASC ,capacity DESC, lod.id)
                UNION 
                (SELECT lod2.id,lod2.type,lod2.capacity 
                FROM %n as lod2 
                LEFT JOIN (SELECT room FROM %n as par
                            LEFT JOIN %n as app
                            ON par.application_id = app.id
                            WHERE app.session = %i) as par2
                ON par2.room = lod2.id
                WHERE lod2.session_%i = 1 AND lod2.session_%i_use = %i and par2.room IS NULL
                ORDER BY lod2.type ASC ,lod2.capacity DESC, lod2.id))",
                    self::T_LODGING,ParticipantsModel::T_PARTICIPANTS,ApplicationsModel::T_APPLICATIONS,
                    $session,$session,self::FOR_PARTICIPANTS,$session+2,
                    self::T_LODGING,ParticipantsModel::T_PARTICIPANTS,ApplicationsModel::T_APPLICATIONS,$session+2,
                    $session,$session,self::FOR_PARTICIPANTS)->as('lod')->fetchAll();

        }


    }

    public function getDataForScript(int $session, int $capacity, int|null $gender, string $faculty,int $first_start): array
    {

        if($gender === null)

            return $this->db->select('par.*')
                ->from('(SELECT participants.*,app.session,app.gender,fac.code as code FROM participants
                              LEFT JOIN applications as app ON app.id = participants.application_id
                              LEFT JOIN faculties as fac ON fac.id = app.faculty
                              WHERE participants.status = 3 AND app.session = %i and participants.room IS NULL
                              ORDER BY participants.create_at
                              LIMIT %i)', $session+2, $capacity)
                ->as('par')
                ->where('par.gender IS NULL and par.lock_room = 0')->fetchAll();
        else
            {
                bdump($this->db->select('par.*')
                    ->from('(SELECT participants.*,app.session,app.gender,fac.code as code FROM participants
                              LEFT JOIN applications as app ON app.id = participants.application_id
                              LEFT JOIN faculties as fac ON fac.id = app.faculty
                              WHERE participants.status = 3 AND app.session = %i 
                              ORDER BY participants.create_at
                              LIMIT %i)', $session+2, $capacity)
                    ->as('par')
                    ->where('par.gender = %i AND par.code = ? and par.lock_room = 0 ', $gender, $faculty)->fetchAll());
            return $this->db->select('par.*')
                ->from('(SELECT participants.*,app.session,app.gender,fac.code as code FROM participants
                              LEFT JOIN applications as app ON app.id = participants.application_id
                              LEFT JOIN faculties as fac ON fac.id = app.faculty
                              WHERE participants.status = 3 AND app.session = %i and participants.room IS NULL
                              ORDER BY participants.create_at
                              LIMIT %i)', $session+2, $capacity)
                ->as('par')
                ->where('par.gender = %i AND par.code = ? and par.lock_room = 0 ', $gender, $faculty)->fetchAll();
        }

    }

    public function setLodgingScript($enable)
    {
        $this->db->update(self::T_LODGING_ACTIVE,['enable'=> $enable])->execute();
    }

    public function setRoomForPar(int $par,int $room,array $old_data )
    {

        $this->db->update(ParticipantsModel::T_PARTICIPANTS, ['room' => $room])->where('application_id = %i', $par)->execute();
        if($old_data[$par]['room'])

            $this->LogLodgingChange($par, AppComponent::SYSTEM_USER, $this->getNameById($old_data[$par]['room']), $this->getNameById($room));
        else
            $this->LogLodgingChange($par, AppComponent::SYSTEM_USER, '', $this->getNameById($room));
    }




     public function setLodgingForAllScript(int $session)
     {



         $old_data = $this->db->select('par.*')
             ->from('%n as par', ParticipantsModel::T_PARTICIPANTS)
             ->leftJoin('%n as app', ApplicationsModel::T_APPLICATIONS)
             ->on('app.id = par.application_id')
             ->where('app.session IS NOT NULL')
             ->fetchAssoc('application_id');

         $this->db->update(ParticipantsModel::T_PARTICIPANTS, ['room' => null])->where('lock_room = 0')->execute();

         if ($session) {
             $groups = $this->db->select('grp.*')
                 ->from('%n as grp', GroupsModel::T_GROUPS)
                 ->leftJoin('%n as app', ApplicationsModel::T_APPLICATIONS)
                 ->on('app.id = grp.create_by')
                 ->where('app.session = %i and grp.count > 1', $session + 2)
                 ->orderBy('grp.count')
                 ->fetchAll();


             $Lodging = $this->getLodgingForPar($session, 0);


             foreach ($groups as $group) {
                 foreach ($Lodging as $room) {
                     if ($group['lodge_type'] == $room['type'] and $group['count'] <= $room['capacity']) {
                         $pars = $this->db->select('*')
                             ->from('%n as grp_par', GroupsModel::T_GROUPS_PARTICIPANTS)
                             ->where('grp_par.group_id = %i', $group['id'])
                             ->fetchAll();
                         $room['capacity'] = $room['capacity'] - $group['count'];
                         foreach ($pars as $par) {
                             $this->setRoomForPar($par['application_id'],$room['id'], $old_data);
                         }
                         break;
                     }
                 }
             }
         } else {

             for ($i = 1; $i < 3; $i++) {


                 $groups = $this->db->select('grp.*')
                     ->from('%n as grp', GroupsModel::T_GROUPS)
                     ->leftJoin('%n as app', ApplicationsModel::T_APPLICATIONS)
                     ->on('app.id = grp.create_by')
                     ->where('app.session = %i and grp.count > 1', $i + 2)
                     ->orderBy('grp.count')
                     ->fetchAll();


                 $Lodging = $this->getLodgingForPar($i, 0);


                 foreach ($groups as $group) {
                     foreach ($Lodging as $room) {
                         if ($group['lodge_type'] == $room['type'] and $group['count'] <= $room['capacity']) {
                             $pars = $this->db->select('*')
                                 ->from('%n as grp_par', GroupsModel::T_GROUPS_PARTICIPANTS)
                                 ->where('grp_par.group_id = %i', $group['id'])
                                 ->fetchAll();
                             $room['capacity'] = $room['capacity'] - $group['count'];
                             foreach ($pars as $par) {
                                 $this->setRoomForPar($par['application_id'],$room['id'], $old_data);
                             }
                             break;
                         }
                     }
                 }
             }

         }





         for($i = 1; $i>=0;$i--)
         {
             $session_1_full_capacity = $this->getCountTypeForPar(1,$i);

             $session_2_full_capacity = $this->getCountTypeForPar(2,$i);

         $divisionParticipants1 = array();
         $divisionParticipants2 = array();

         $divisionParticipants1["female"] = array();
         $divisionParticipants1["male"] = array();

         $divisionParticipants2["female"] = array();
         $divisionParticipants2["male"] = array();

         $schools = $this->db->select('code as school')->from(FacultiesModel::T_SCHOOLS)->fetchAll();


         foreach ($schools as $school) {
             $faculties = $this->facultiesModel->getGrid()->where('schools.code = ?', $school['school'])->fetchAll();
             $divisionParticipants1["female"][$school['school']] = array();
             $divisionParticipants1["male"][$school['school']] = array();
             $divisionParticipants2["female"][$school['school']] = array();
             $divisionParticipants2["male"][$school['school']] = array();

             foreach ($faculties as $faculty) {
                 $divisionParticipants1["female"][$school['school']][$faculty['code']] = $this->getDataForScript(1, $session_1_full_capacity, 1, $faculty['code'], 1);
                 $divisionParticipants1["male"][$school['school']][$faculty['code']] = $this->getDataForScript(1, $session_1_full_capacity, 2, $faculty['code'], 1);
                 $divisionParticipants2["female"][$school['school']][$faculty['code']] = $this->getDataForScript(2, $session_2_full_capacity, 1, $faculty['code'], 1);
                 $divisionParticipants2["male"][$school['school']][$faculty['code']] = $this->getDataForScript(2, $session_2_full_capacity, 2, $faculty['code'], 1);
             }
         }

         if ($session != 2)
             $Lodging_s1 = $this->getLodgingForPar(1, 0);
         else
             $Lodging_s1 = array();
         if ($session != 1)
             $Lodging_s2 = $this->getLodgingForPar(2, 0);
         else
             $Lodging_s2 = array();



         //in faculty is E or B number of people
         for ($lod_i = 0; $lod_i < count($Lodging_s1); $lod_i++) {
             foreach ($divisionParticipants1 as $key_gender => $div_gender) {
                 foreach ($div_gender as $key_school => $div_school) {
                     foreach ($div_school as $key_faculty => $div_faculty) {
                         if (count($div_faculty) >= $Lodging_s1[$lod_i]['capacity']) {
                             for ($var = 0; $var < $Lodging_s1[$lod_i]['capacity']; $var++) {
                                 $this->setRoomForPar(
                                     array_shift(
                                         $divisionParticipants1[$key_gender][$key_school][$key_faculty])['application_id'],
                                     $Lodging_s1[$lod_i]['id'], $old_data);
                             }

                             array_splice($Lodging_s1, $lod_i, 1);
                             $lod_i--;
                             break 3;
                         }
                     }
                 }
             }
         }


         //in faculty is E or B number of people
         for ($lod_i = 0; $lod_i < count($Lodging_s2); $lod_i++) {
             foreach ($divisionParticipants2 as $key_gender => $div_gender) {
                 foreach ($div_gender as $key_school => $div_school) {
                     foreach ($div_school as $key_faculty => $div_faculty) {
                         if (count($div_faculty) >= $Lodging_s2[$lod_i]['capacity']) {
                             for ($var = 0; $var < $Lodging_s2[$lod_i]['capacity']; $var++) {
                                 $this->setRoomForPar(
                                     array_shift(
                                         $divisionParticipants2[$key_gender][$key_school][$key_faculty])['application_id'],
                                     $Lodging_s2[$lod_i]['id'], $old_data);
                             }
                             array_splice($Lodging_s2, $lod_i, 1);
                             $lod_i--;
                             break 3;
                         }
                     }
                 }
             }
         }
         foreach ($divisionParticipants1 as $key_gender => $div_gender) {
             foreach ($div_gender as $key_school => $div_school) {
                 $new_div_school = array();
                 foreach ($div_school as $div_faculty) {
                     $new_div_school = array_merge($new_div_school, $div_faculty);
                 }
                 $divisionParticipants1[$key_gender][$key_school] = $new_div_school;
             }
         }

         foreach ($divisionParticipants2 as $key_gender => $div_gender) {
             foreach ($div_gender as $key_school => $div_school) {
                 $new_div_school = array();
                 foreach ($div_school as $div_faculty) {
                     $new_div_school = array_merge($new_div_school, $div_faculty);
                 }
                 $divisionParticipants2[$key_gender][$key_school] = $new_div_school;
             }
         }

         for ($lod_i = 0; $lod_i < count($Lodging_s1); $lod_i++) {
             foreach ($divisionParticipants1 as $key_gender => $div_gender) {
                 foreach ($div_gender as $key_school => $div_school) {
                     if (count($div_school) >= $Lodging_s1[$lod_i]['capacity']) {
                         for ($var = 0; $var < $Lodging_s1[$lod_i]['capacity']; $var++) {
                             $this->setRoomForPar(
                                 array_shift(
                                     $divisionParticipants1[$key_gender][$key_school])['application_id'],
                                 $Lodging_s1[$lod_i]['id'], $old_data);
                         }

                         array_splice($Lodging_s1, $lod_i, 1);
                         $lod_i--;
                         break 2;
                     }
                 }
             }
         }

         for ($lod_i = 0; $lod_i < count($Lodging_s2); $lod_i++) {
             foreach ($divisionParticipants2 as $key_gender => $div_gender) {
                 foreach ($div_gender as $key_school => $div_school) {
                     if (count($div_school) >= $Lodging_s2[$lod_i]['capacity']) {
                         for ($var = 0; $var < $Lodging_s2[$lod_i]['capacity']; $var++) {
                             $this->setRoomForPar(
                                 array_shift(
                                     $divisionParticipants2[$key_gender][$key_school])['application_id'],
                                 $Lodging_s2[$lod_i]['id'], $old_data);
                         }
                         array_splice($Lodging_s2, $lod_i, 1);
                         $lod_i--;
                         break 2;
                     }
                 }
             }
         }

         $new_divisionParticipants1 = array();
         foreach ($divisionParticipants1 as $key_gender => $div_gender) {
             foreach ($div_gender as $key_school => $div_school) {

                 $new_divisionParticipants1 = array_merge($new_divisionParticipants1, $div_school);
             }
         }
         $new_divisionParticipants1 = array_merge($new_divisionParticipants1, $this->getDataForScript(1, $session_1_full_capacity, null, '', 0));
         $divisionParticipants1 = $new_divisionParticipants1;

         $new_divisionParticipants2 = array();
         foreach ($divisionParticipants2 as $key_gender => $div_gender) {
             foreach ($div_gender as $key_school => $div_school) {

                 $new_divisionParticipants2 = array_merge($new_divisionParticipants2, $div_school);
             }
         }
         $new_divisionParticipants2 = array_merge($new_divisionParticipants2, $this->getDataForScript(2, $session_2_full_capacity, null, '', 0));
         $divisionParticipants2 = $new_divisionParticipants2;

         if ($session != 2)
             $Lodging_s1 = $this->getLodgingForPar(1, 0);

         if ($session != 1)
             $Lodging_s2 = $this->getLodgingForPar(2, 0);


         //remaining
         if ($session != 2)
             for ($var = 0; $var < count($divisionParticipants1);) {
                 if (!$Lodging_s1[0]['capacity']) {
                     array_splice($Lodging_s1, 0, 1);
                     continue;
                 }
                 $this->setRoomForPar(array_shift($divisionParticipants1)['application_id'], $Lodging_s1[0]['id'], $old_data);
                 $Lodging_s1[0]['capacity'] = $Lodging_s1[0]['capacity'] - 1;
                 if (!$Lodging_s1[0]['capacity']) {

                     array_splice($Lodging_s1, 0, 1);
                 }
             }

         //remaining
         if ($session != 1)
             for ($var = 0; $var < count($divisionParticipants2);) {
                 if (!$Lodging_s2[0]['capacity']) {
                     array_splice($Lodging_s2, 0, 1);
                     continue;
                 }
                 $this->setRoomForPar(array_shift($divisionParticipants2)['application_id'], $Lodging_s2[0]['id'], $old_data);
                 $Lodging_s2[0]['capacity'] = $Lodging_s2[0]['capacity'] - 1;
                 if (!$Lodging_s2[0]['capacity']) {
                     array_splice($Lodging_s2, 0, 1);
                 }
             }

        }
    }


    public function IsForBung(int $id,int $session): int
    {

        $max_Bung = $this->getMaxBungForSession($session);
        return count($this->db->select('prt.id')
            ->from('(SELECT  app.id as id
                                          FROM %n AS prt
                                          LEFT JOIN %n as app ON app.id = prt.application_id
                                          WHERE prt.status = 3 AND app.session = %i
                                          ORDER BY prt.create_at
                                          LIMIT %i) as prt',
                ParticipantsModel::T_PARTICIPANTS, ApplicationsModel::T_APPLICATIONS, $session, $max_Bung)
            ->where('prt.id = %i', $id)
            ->fetchAll());

    }

    public function getMaxBungForSession(int $session): int
    {
        return $this->db->select('ses.first_lodging_capacity')
            ->from('%n AS ses', SessionsModel::T_SESSIONS)
            ->where(' ses.id = %i',$session)
            ->fetchSingle();
    }

    public function getLodgingForGroups(int $session , int $without = 0,int $inv_send_to = 0): array
    {
        if($without)
        {
            $Bung_groups = $this->db->select('grp.id,grp.count')
                ->from('%n as grp', GroupsModel::T_GROUPS)

                ->where('lodge_type = 1 and count > 1 and grp.id != %i',$without)
                ->orderBy('grp.count')
                ->fetchAssoc('id');

            $Hut_groups = $this->db->select('grp.id,grp.count')
                ->from('%n as grp', GroupsModel::T_GROUPS)
                ->where('lodge_type = 2 and count > 1 and grp.id != %i',$without)
                ->orderBy('grp.count')
                ->fetchAssoc('id');
        }
        elseif($inv_send_to)
        {
            $Bung_groups = $this->db->select('grp.id,grp.count')
                ->from('%n as grp', GroupsModel::T_GROUPS)
                ->where('grp.id NOT IN ( Select inv.group_id from %n as inv where inv.send_to = %i)',GroupsModel::T_INVITATIONS,$inv_send_to)
                ->where('lodge_type = 1 and count > 1')
                ->orderBy('grp.count')
                ->fetchAssoc('id');

            $Hut_groups = $this->db->select('grp.id,grp.count')
                ->from('%n as grp', GroupsModel::T_GROUPS)
                ->where('grp.id NOT IN ( Select inv.group_id from %n as inv where inv.send_to = %i)',GroupsModel::T_INVITATIONS,$inv_send_to)
                ->where('lodge_type = 2 and count > 1')
                ->orderBy('grp.count')
                ->fetchAssoc('id');

        }
        else
        {
            $Bung_groups = $this->db->select('grp.id,grp.count')
                ->from('%n as grp', GroupsModel::T_GROUPS)
                ->where('lodge_type = 1 and count > 1')
                ->orderBy('grp.count')
                ->fetchAssoc('id');

            $Hut_groups = $this->db->select('grp.id,grp.count')
                ->from('%n as grp', GroupsModel::T_GROUPS)
                ->where('lodge_type = 2 and count > 1')
                ->orderBy('grp.count')
                ->fetchAssoc('id');
        }


        $FreePlaces = array();

        $FreePlaces['Bung'] = array();

        $bung = $this->db->select('lod.capacity as cap,COUNT(*) as count')
                 ->from( self::T_LODGING)->as('lod')
                 ->where('lod.type = 1 and lod.session_%i = 1 AND lod.session_%i_use = %i',$session-2,$session-2,self::FOR_PARTICIPANTS)
                 ->groupBy('lod.capacity')
                 ->fetchAssoc('cap');

        for($i = 2; $i < 7; $i++)
        {
            if(isset($bung[$i]))
            {
                $FreePlaces['Bung'][$i] = $bung[$i]['count'];
            }
            else
            {
                $FreePlaces['Bung'][$i] = 0;
            }
        }

        $FreePlaces['Hut'] = array();

        $hut = $this->db->select('lod.capacity as cap,COUNT(*) as count')
            ->from( self::T_LODGING)->as('lod')
            ->where('lod.type = 2 and lod.session_%i = 1 AND lod.session_%i_use = %i',$session-2,$session-2,self::FOR_PARTICIPANTS)
            ->groupBy('lod.capacity')
            ->fetchAssoc('cap');

        for($i = 2; $i < 7; $i++)
        {
            if(isset($hut[$i]))
            {
                $FreePlaces['Hut'][$i] = $hut[$i]['count'];
            }
            else
            {
                $FreePlaces['Hut'][$i] = 0;
            }
        }

        foreach ($Bung_groups as $group)
        {
            for($i=0;$group['count']+$i<7;$i++)
            {
                if ($FreePlaces['Bung'][$group['count'] + $i] > 0)
                {
                    $FreePlaces['Bung'][$group['count'] + $i] = $FreePlaces['Bung'][$group['count'] + $i] - 1;
                    if($i>1)
                    {
                        $FreePlaces['Bung'][$i] = $FreePlaces['Bung'][$i]  + 1;
                    }
                    break;
               }
            }
        }

        foreach ($Hut_groups as $group)
        {
            for($i=0;$group['count']+$i<7;$i++)
            {
                if ($FreePlaces['Hut'][$group['count'] + $i] > 0)
                {
                    $FreePlaces['Hut'][$group['count'] + $i] = $FreePlaces['Hut'][$group['count'] + $i] - 1;
                    if($i>1)
                    {
                        $FreePlaces['Hut'][$i] = $FreePlaces['Hut'][$i]  + 1;
                    }
                    break;
                }
            }
        }

        return $FreePlaces;
    }
}
