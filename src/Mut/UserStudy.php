<?php
namespace App\Mut;
use App\Mut\DB;
use DateTime;
use Doctrine\DBAL\Connection;
// use Doctrine\DBAL\Types;
class UserStudy{
    public function getStudy($expertiseId){
        // NOTE: use strategy where each user get's assigned different samples
        $conn  = DB::getConnection();
        // $conn->createQueryBuilder()->select('user', 'COUNT(*)')->from('likert')->addGroupBy('user')->executeQuery();
        // $res = $conn->createQueryBuilder()->select('*')->from('samples')->getSQL()->executeQuery();
        # NOTE: not thread safe. Maybe most safe is simply to find the paths which havent been used before and check on commit that they are not there.
        # change because if people are doing simultaneosly they will get the same.
        // $res = $conn->createQueryBuilder()->select('COUNT(user)')->distinct()->from('likert');
        // $nUsers = $res->fetchFirstColumn()[0];
        // $userId = $nUsers;
        $userId = $conn->transactional(function(Connection $conn) use ($expertiseId): int  {
            // $queryBuilder = $conn->createQueryBuilder();
            // $ret = $queryBuilder->select('MIN(id)+1')->from('user')->where($queryBuilder->expr()->notIn('id +1',
            //     $queryBuilder->expr()->select('id')->from('user')
            // ));
            $ret = $conn->prepare("SELECT MIN(id) +1 FROM USER WHERE  id + 1 NOT IN (SELECT id FROM user)")->executeQuery();
            $nextId = $ret->fetchFirstColumn()[0];
            if( is_null($nextId)){
                $nextId = 0;
            }
            #TODO: check succes...
            // $now = $conn->createQueryBuilder()->fetchColumn("SELECT NOW()");
            $ret = $conn->createQueryBuilder()->insert('user')->values( ['id'=>$nextId, 'expertise'=>'?', 'create_time'=> '?'])->setParameters([0 => $expertiseId, 1=>(new DateTime())->format(DateTime::ISO8601)])->executeQuery();
            // $ret = $conn->prepare("INSERT INTO user (id, create_time) VALUES ($nextId, CURRENT_TIMESTAMP)")->executeStatement();
            return $nextId;
        
        });
            // $queryBuilder->insert('user')->values(['id'=>'?', 'create_time'=>'CURRENT_TIMESTAMP']).values(0, $userId);
            // SELECT MIN(id) +1 FROM USER WHERE  id + 1 NOT IN (SELECT id FROM user);

//             SELECT MIN(a) + 1
//   FROM user
//  WHERE a + 1 NOT IN (SELECT a FROM t)
        //     return $conn->fetchOne('SELECT 1');
        // });
        // NOTE: can maybe be done in one query...
        
        $samplesPrMethod = Config::getConfig()['samples_per_method'];
        // $sql = "SELECT a.method,
        //     GROUP_CONCAT((
        //         SELECT b.id 
        //         FROM samples b 
        //         WHERE b.method = a.method
        //         ORDER BY id 
        //         LIMIT $userId,$samplesPrUser
        //     ), '!') as id
        //     FROM samples a
        //     GROUP BY a.method
        //     ";
        $sql = "SELECT a.method,
        (
            SELECT b.id
            FROM samples b 
            WHERE b.method = a.method
            ORDER BY id 
            LIMIT ?, 1
        )  as id
        FROM samples a
        GROUP BY a.method
        ";
        $prepared = $conn->prepare($sql);
        $samples = [];
        for($offset=$samplesPrMethod*$userId; $offset<$samplesPrMethod*$userId+$samplesPrMethod; $offset++){
            $res = $prepared->executeQuery([$offset])->fetchAllAssociative();
            foreach($res as $item){
                // $method = $item['method'];
                // $arr = $samples[$method] ?? [];
                // array_push($arr, $item['id']);
                // $samples[$method] = $arr;
                array_push($samples, $item);
            }
        }
       
        // $sql = "
        //     SELECT GROUP_CONCAT(b.id, '!')
        //     FROM samples b 
        //     ORDER BY id 
        //     LIMIT $userId,$samplesPrUser
        // ";
        // $methodGroups = $conn->createQueryBuilder()->select("GROUP_CONCAT(id, '!')")->from('samples')->groupBy('method')->fetchAllAssociative();
        // $samples = [];
        // foreach ($methodGroups as $method=>$samples_){
        //     $paths = explode('!', array_values($samples_)[0]);
        //     $samples[$method] = $paths[$userId];
        // }
        $exp = ['user'=>$userId, 'samples'=>$samples];
        return $exp;
    }
}