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
        try{
            $exp = $conn->transactional(function(Connection $conn) use ($expertiseId): array  {
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
                // return $nextId;
                $userId = $nextId;
                $samplesPrMethod = Config::getConfig()['samples_per_method'];
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
                        if (is_null($item['id'])){
                            throw new \Exception('Too few samples in database');
                        }
                        array_push($samples, $item);
                    }
                }
                $nMethods = $conn->executeQuery('SELECT COUNT(*) FROM sampling_method')->fetchFirstColumn()[0];
                if(count($samples) != $samplesPrMethod * $nMethods ){
                    throw new \Exception("Too few samples in database.");
                }
                return ['user'=>$userId, 'samples'=>$samples];
            });
        }
        catch (\Throwable $e){
            $exp = null;
        }
        return $exp;
    }
}