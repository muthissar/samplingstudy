<?php

namespace App\Mut;
use DirectoryIterator;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;
use \Doctrine\DBAL\Schema\Schema;
use \App\Mut\Config;
use Doctrine\DBAL\Types;
use RecursiveDirectoryIterator;
use FilesystemIterator;
class DB{
    static $config;
    protected Connection $conn;
    public function __construct(){
        $this->conn = DB::getConnection();
    }
    function __destruct() {
        $this->conn->close();
    }

    public static function getConnection() : Connection{
        $connectionParams = [
            'path' => __DIR__."/../../".self::$config['db_name'],
            'driver' => 'sqlite3',
        ];
        return DriverManager::getConnection($connectionParams);
    }
    public static function getSchema(){
        $schema = new Schema();
        // https://stackoverflow.com/questions/23979898/php-doctrine-dbal-schema-autoincrement-doesnt-work
        $likertTable = $schema->createTable("likert");
        $idColumn = $likertTable->addColumn("id", "integer", ["unsigned" => true]);
        $idColumn->setAutoincrement(true);
        $likertTable->setPrimaryKey(["id"]);
        $likertTable->addColumn("user", "string", ["length" => 32, /*"notnull" => false*/]);
        // $likertTable->addColumn("group", "string", ["length" => 32, /*"notnull" => false*/]);
        $likertTable->addColumn("sample", "integer", ["unsigned" => true]);
        foreach (array_keys(self::$config['likert']) as &$method) {
            $likertTable->addColumn($method, "integer", ["unsigned" => true, /*"notnull" => false*/]);
        }
        $timeColumn = $likertTable->addColumn("time", 'datetime');
        // $likertTable->addUniqueIndex(["user"]);
        // $likertTable->setComment('Keeps the answers of users.');
        $userTable = $schema->createTable("user");
        $userTable->addColumn("id", "integer", ["unsigned" => true]);
        $userTable->addColumn("expertise", "integer", ["unsigned" => true, "notnull" => false]);
        $userTable->addColumn("create_time", 'datetime');

        $userExpertiseTable = $schema->createTable("expertise");
        $idColumn = $userExpertiseTable->addColumn("id", "integer", ["unsigned" => true]);
        $idColumn->setAutoincrement(true);
        $userExpertiseTable->addColumn("level", "string", ["length" => 32]);
        $userExpertiseTable->setPrimaryKey(["id"]);

        $sampleTable = $schema->createTable("samples");
        $idColumn = $sampleTable->addColumn("id", "integer", ["unsigned" => true]);
        $idColumn->setAutoincrement(true);
        $sampleTable->setPrimaryKey(["id"]);
        $sampleTable->addColumn("method", "integer", ["unsigned" => true]);
        $sampleTable->addColumn("path", "string", ["length" => 32, /*"notnull" => false*/]);

        $sampleMethodTable = $schema->createTable("sampling_method");
        $idColumn = $sampleMethodTable->addColumn("id", "integer", ["unsigned" => true]);
        $sampleMethodTable->setPrimaryKey(["id"]);
        $sampleMethodTable->addColumn("name", "string", ["length" => 32, /*"notnull" => false*/]);
        
        
        return $schema;
    }
    public static function getPlatform(){
        return new \Doctrine\DBAL\Platforms\SqlitePlatform();
    }
    public static function drop() : void{
        $schema = self::getSchema();
        $myPlatform = self::getPlatform();
        $queries = $schema->toDropSql($myPlatform); // get queries to safely delete this schema.
        $conn = (new DB())->conn;;

        foreach ($queries as &$query) {
            $conn->executeQuery($query);
        }
    }
    public function create() : void{
        $schema = DB::getSchema();
        $myPlatform = DB::getPlatform();
        $queries = $schema->toSql($myPlatform); // get queries to create this schema.
        // $dropSchema = $schema->toDropSql($myPlatform); // get queries to safely delete this schema.
        $conn = (new DB())->conn;;

        foreach ($queries as $query) {
            $conn->executeQuery($query);
        }
        
        #NOTE: fill sampling_method table
        $methods = [];
        $methodCounter = 0;
        $sampleDir = self::$config['sample_dir'];
        // foreach (self::$config['sampling_methods'] as $method) {
        foreach (new DirectoryIterator(__DIR__."/../../$sampleDir") as $file) {
            if($file->isDot()) continue;
            if($file->isDir()){
                $method = $file->getBasename();
                $conn->insert('sampling_method', ['id'=>$methodCounter, 'name' =>$method]);
                $methods[$method] = $methodCounter;
            }
            $methodCounter++;
        }
        
        #NOTE: fill expertise table
        foreach(self::$config['expertise'] as $expertise){
            $conn->insert('expertise', ['level' =>$expertise]);
        }

        # NOTE: fill samples
        # NOTE: shuffle files (seeded for reproducability)
        mt_srand(0);
        foreach($methods as $method => $methodId){
            $files = iterator_to_array(
                new RecursiveDirectoryIterator(__DIR__."/../../$sampleDir/$method"),
                FilesystemIterator::SKIP_DOTS
            );
            asort($files);
            shuffle($files);
            // $files = new DirectoryIterator(__DIR__."/../../$sampleDir/$method");
            // $files = [];
            // array_push($files, new DirectoryIterator(__DIR__."/../../$sampleDir/$method"));
            foreach ($files as $file) {
                // if($file->isDot()) continue;
                $p = $file->getPathname();
                $conn->insert('samples', ['method'=>$methodId, 'path'=>$p]);
            }
        }
    }
    public function insert($answer, $samplingMethod) : void{
        $queryBuilder = $this->conn->createQueryBuilder();
        $insertStm = $queryBuilder->insert('user_table')->values(array('user'=> '?'));
        // $insertStm->setParameters(array('user'=>'test_user'))->executeQuery();

        $insertStm->setParameter(0, 'test_user')->executeQuery();

    }

    public function clear_tables() : void {
        $queryBuilder = $this->conn->createQueryBuilder();
        $queryBuilder->delete("user")->executeQuery();
        $queryBuilder->delete("likert")->executeQuery();
    }
};
DB::$config = Config::getConfig();