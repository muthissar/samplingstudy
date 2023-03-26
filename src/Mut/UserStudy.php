<?php
namespace App\Mut;
use App\Mut\DB;
class UserStudy{
    public function getStudy(){
        // NOTE: use strategy where each user get's assigned different samples
        $conn  = DB::getConnection();
        // $conn->createQueryBuilder()->select('user', 'COUNT(*)')->from('likert')->addGroupBy('user')->executeQuery();
        // $res = $conn->createQueryBuilder()->select('*')->from('samples')->getSQL()->executeQuery();
        # NOTE: not thread safe. Maybe most safe is simply to find the paths which havent been used before and check on commit that they are not there.
        # change because if people are doing simultaneosly they will get the same.
        $res = $conn->createQueryBuilder()->select('COUNT(user)')->distinct()->from('likert');
        $nUsers = $res->fetchFirstColumn()[0];
        $userId = $nUsers;
        // NOTE: can maybe be done in one query...
        $methodGroups = $conn->createQueryBuilder()->select("GROUP_CONCAT(id, '!')")->from('samples')->groupBy('method')->fetchAllAssociative();
        $samples = [];
        foreach ($methodGroups as $method=>$samples_){
            $paths = explode('!', array_values($samples_)[0]);
            $samples[$method] = $paths[$userId];
        }
        $exp = ['user'=>$userId, 'samples'=>$samples];
        return $exp;
    }
}