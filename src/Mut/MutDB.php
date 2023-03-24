<?php

namespace App\Mut;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;
use \Doctrine\DBAL\Schema\Schema;

class MutDB{
    protected Connection $conn;
    public function __construct(){
        $this->conn = MutDB::getConnection();
    }
    function __destruct() {
        $this->conn->close();
    }

    public static function getConnection() : Connection{
        $connectionParams = [
            // 'user' => 'admin',
            // 'password' => 'secret',
            // // TODO: change path
            // 'driver' => 'pdo_sqlite',
            'path' => './listener_db.sqlite',
            'driver' => 'sqlite3',
        ];
        return DriverManager::getConnection($connectionParams);
    }
    public function createDB() : void{

        // $config = yaml_parse_file('./config.yaml');
        $schema = new Schema();
        $userTable = $schema->createTable("user_table");
        // https://stackoverflow.com/questions/23979898/php-doctrine-dbal-schema-autoincrement-doesnt-work
        $idColumn = $userTable->addColumn("id", "integer", ["unsigned" => true]);
        $idColumn->setAutoincrement(true);
        $userTable->addColumn("sampling_method", "integer", ["unsigned" => true]);
        $userTable->addColumn("sample_id", "integer", ["unsigned" => true]);
        $userTable->addColumn("user", "string", ["length" => 32, /*"notnull" => false*/]);
        $arr = array(
            'stm_struct',
            'ltm_struct',
            'overall_preference',
            'complexity',
            'coherence',
            'repetitiveness'
        );
        foreach ($arr as &$method) {
        $userTable->addColumn($method, "integer", ["unsigned" => true, /*"notnull" => false*/]);
        }

        $userTable->setPrimaryKey(["id"]);
        $userTable->addUniqueIndex(["user"]);
        $userTable->setComment('Keeps the answers of users.');

        $sampleTable = $schema->createTable("sample_table");
        $sampleTable->addColumn("id", "integer", ["unsigned" => true]);
        $sampleTable->addColumn("sampling_method", "integer", ["unsigned" => true]);
        $sampleTable->addColumn("sampling_method_id", "integer", ["unsigned" => true]);
        $sampleTable->setPrimaryKey(["id"]);

        $myPlatform = new \Doctrine\DBAL\Platforms\SqlitePlatform();
        $queries = $schema->toSql($myPlatform); // get queries to create this schema.
        // $dropSchema = $schema->toDropSql($myPlatform); // get queries to safely delete this schema.
        $conn = $conn = (new MutDB())->conn;;

        foreach ($queries as &$query) {
            $conn->executeQuery($query);
        }
    }
    public function insert() : void{
        $queryBuilder = $this->conn->createQueryBuilder();
        $insertStm = $queryBuilder->insert('user_table')->values(array('user'=> '?'));
        // $insertStm->setParameters(array('user'=>'test_user'))->executeQuery();

        $insertStm->setParameter(0, 'test_user')->executeQuery();

    }
};