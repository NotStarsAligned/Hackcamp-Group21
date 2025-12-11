<?php

class StaffData
{

    public $_id, $_user_id, $_skills, $current_status, $last_latitude, $last_longitude, $last_updated_at;

    public function __construct($dbRow)
    {
        $this->_id = $dbRow["id"] ?? null;
        $this->_user_id = $dbRow["user_id"] ?? null;
        $this->_skills = $dbRow["skills"] ?? null;
        $this->current_status = $dbRow["current_status"] ?? null;
        $this->last_latitude = $dbRow["last_latitude"] ?? null;
        $this->last_longitude = $dbRow["last_longitude"] ?? null;
        $this->last_updated_at = $dbRow["last_updated_at"] ?? null;
    }

    public function getId(): mixed
    {
        return $this->_id;
    }

    public function getUserId(): mixed
    {
        return $this->_user_id;
    }

    public function getSkills(): mixed
    {
        return $this->_skills;
    }

    public function getCurrentStatus(): mixed
    {
        return $this->current_status;
    }

    public function getLastLatitude(): mixed
    {
        return $this->last_latitude;
    }

    public function getLastLongitude(): mixed
    {
        return $this->last_longitude;
    }

    public function getLastUpdatedAt(): mixed
    {
        return $this->last_updated_at;
    }


}