<?php
//TEK

session_start();
// Assuming "database.php" contains your first Database class (Code 1)
require_once ("database.php");
require_once ("StaffData.php");

class StaffDataSet {
    protected $_dbHandle, $_dbInstance;

    public function __construct(){
        // 1. Uses getInstance() from the original Database class
        $this->_dbInstance = Database::getInstance();

        // 2. Uses getConnection() from the original Database class
        $this->_dbHandle = $this->_dbInstance->getConnection();
    }

    /**
     * Retrieves the Latitude for a specific staff ID.
     * * @param int $id The ID of the staff member to look up.
     * @return string The Latitude value as a string.
     */
    public function getLat(int $id = 1): string
    {
        // Parameterized query to prevent SQL injection
        $query = "SELECT last_latitude FROM staff_profiles WHERE id = ?";

        // Prepare the statement
        $statement = $this->_dbHandle->prepare($query);

        // Execute the statement with the ID parameter
        $statement->execute([1]);

        // 3. IMPORTANT: Use fetchColumn() to get the value of the first column
        // This is a cleaner way to fetch a single value (like Latitude)
        // when you know the query only returns one row/one column.
        $latitude = $statement->fetchColumn();

        // Return the fetched Latitude, or an empty string if not found
        return $latitude !== false ? (string)$latitude : "";
    }


}
// --- Example Usage ---

// $staffData = new StaffDataSet();
// $latitude = $staffData->getLat(101);
// echo "Staff Latitude: " . $latitude;

?>