<?php

class Database{
    private static $instance=null;
    private $conn;

    private function __construct(){
        //path to database
        $dbPath = realpath(__DIR__.'/../database/people.sqlite/');
        if (!$dbPath){
            die("Database path not set");
        }
        $this->conn = new PDO("sqlite" . $dbPath);
        //exception
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    public static function getInstance(){
        if (self::$instance==null){
            self::$instance = new Database();
        }
        return self::$instance;
    }
    public function getConnection():PDO {
        return $this->conn;
    }

}
