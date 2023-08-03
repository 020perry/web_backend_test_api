<?php


namespace App\Models;

use App\Plugins\Http\Exceptions\BadRequest;
use App\Plugins\Http\Exceptions\NotFound;
use Exception;
use PDO;

/**
 * Class FacilityModel
 *
 * Represents the facility model that handles database operations related to facilities, tags, and locations.
 */
class FacilityModel
{
    /**
     * @var PDO $db Database connection
     */
    private $db;

    /**
     * FacilityModel constructor.
     *
     * @param PDO $db Database connection
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Checks if a facility with the given name exists in the database.
     *
     * @param string $name Name of the facility
     *
     * @return array|null  Facility data if facility exists, null otherwise
     */
    public function checkFacilityExists($name)
    {
        // Define the SQL query that will check if a facility with the provided name exists
        $query = 'SELECT id FROM Facility WHERE name = :name';

        // Execute the query with the provided name as a parameter
        $this->db->executeQuery($query, [':name' => $name]);

        // Fetch and return the result of the query. This will be an associative array if a facility with the provided name exists, or false if not.
        return $this->db->getStatement()->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Creates a new facility in the database.
     *
     * @param string $name The name of the facility.
     * @param int $location_id The ID of the location associated with the facility.
     *
     * @return int The ID of the newly created facility.
     *
     * @throws BadRequest If the input data is not valid (e.g., $name is not a string or $location_id is not an integer).
     */

    public function createFacility($name, $location_id)
    {
        // Check the data types of the input parameters
        if (!is_string($name) || !is_int($location_id)) {
            // If the name is not a string or the location ID is not an integer, throw a BadRequest exception
            throw new BadRequest('Invalid facility data');
        }

        // Define the SQL query that will insert a new facility with the provided name and location ID, and the current date and time as the creation date
        $query = 'INSERT INTO Facility (name, location_id, creation_date) VALUES (:name, :location_id, NOW())';

        // Execute the query with the provided name and location ID as parameters
        $this->db->executeQuery($query, [':name' => $name, ':location_id' => $location_id]);

        // Return the ID of the newly created facility
        return $this->db->getLastInsertedId();
    }


    /**
     * Creates a new location in the database.
     *
     * @param array $location_data An associative array containing the location data.
     *                               The array must include the following keys: 'city', 'address', 'zip_code', 'country_code', and 'phone_number'.
     *                               Each corresponding value should be a string.
     *
     * This function validates the location data. If a required field is missing or is not a string,
     * it throws a BadRequest exception with a message specifying the missing or invalid field.
     *
     * After validating the data, this function inserts a new row into the 'Location' table in the database.
     * It uses the PDO::lastInsertId method to retrieve the ID of the newly inserted location.
     *
     * @return int  The ID of the newly created location.
     *
     * @throws BadRequest  If a required field is missing or is not a string in $location_data.
     */
    public function createLocation($location_data)
    {
        // List of required fields
        $required_fields = ['city', 'address', 'zip_code', 'country_code', 'phone_number'];

        // Check each required field
        foreach ($required_fields as $field) {
            // If the field is not set or is not a string, throw a BadRequest exception with a specific message
            if (!isset($location_data[$field]) || !is_string($location_data[$field])) {
                throw new BadRequest("Invalid location data: missing or invalid '$field'");
            }
        }

        $query = 'INSERT INTO Location (city, address, zip_code, country_code, phone_number) VALUES (:city, :address, :zip_code, :country_code, :phone_number)';
        $this->db->executeQuery($query, $location_data);
        return $this->db->getLastInsertedId();
    }

    /**
     * Creates a new tag in the database or returns the ID if it already exists.
     *
     * @param string $tag Name of the tag
     *
     * @return int  ID of the tag
     *
     * @throws BadRequest If the input data is not valid
     */
    public function createTag($tag)
    {
        // Check if the provided tag is a string; throw an exception if not.
        if (!is_string($tag)) {
            throw new BadRequest('Invalid tag data');
        }

        // Prepare the SQL query to select the tag ID with the given name.
        $query = 'SELECT id FROM Tag WHERE name = :name';
        // Execute the prepared query with the provided tag name parameter.
        $this->db->executeQuery($query, [':name' => $tag]);
        // Fetch the tag ID from the database, if available.
        $tag_id = $this->db->getStatement()->fetchColumn();

        // If the tag doesn't exist in the database, insert it as a new row.
        if (!$tag_id) {
            // Prepare the SQL query to insert a new tag into the Tag table.
            $query = 'INSERT INTO Tag (name) VALUES (:name)';
            // Execute the prepared query with the provided tag name parameter.
            $this->db->executeQuery($query, [':name' => $tag]);
            // Get the ID of the newly inserted tag.
            $tag_id = $this->db->getLastInsertedId();
        }

        // Return the ID of the newly created or existing tag.
        return $tag_id;
    }

    /**
     * Creates a link between a facility and a tag in the database.
     *
     * @param int $facility_id ID of the facility
     * @param int $tag_id ID of the tag
     *
     * @throws BadRequest If the input data is not valid
     */
    public function createFacilityTag($facility_id, $tag_id)
    {
        // Check if both the provided facility ID and tag ID are integers; throw an exception if not.
        if (!is_int($facility_id) || !is_int($tag_id)) {
            throw new BadRequest('Invalid facility or tag data');
        }

        // Prepare the SQL query to insert a new facility-tag association into the database.
        $query = 'INSERT INTO Facility_Tag (facility_id, tag_id) VALUES (:facility_id, :tag_id)';

        // Execute the prepared query with the provided facility ID and tag ID parameters.
        $this->db->executeQuery($query, [':facility_id' => $facility_id, ':tag_id' => $tag_id]);
    }

    /**
     * Retrieves all tags associated with a facility from the database.
     *
     * @param int $facility_id ID of the facility
     *
     * @return array  List of tags associated with the facility
     *
     * @throws BadRequest If the input data is not valid
     */
    public function getTagsByFacilityId($facility_id)
    {
        // Check if the provided facility ID is an integer; throw an exception if not.
        if (!is_int($facility_id)) {
            throw new BadRequest('Invalid facility ID');
        }

        // Prepare the SQL query to fetch tags associated with the facility by its ID.
        $query = 'SELECT t.name FROM Tag t INNER JOIN Facility_Tag ft ON ft.tag_id = t.id WHERE ft.facility_id = :facility_id';

        // Execute the prepared query with the provided facility ID parameter.
        $this->db->executeQuery($query, [':facility_id' => $facility_id]);

        // Fetch all rows of results (tags) as associative arrays and return the array.
        return $this->db->getStatement()->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves a facility from the database by its ID.
     *
     * @param int $id ID of the facility
     *
     * @return array|null  Facility data if facility exists, null otherwise
     *
     * @throws BadRequest If the input data is not valid
     * @throws NotFound If no facility with the given ID exists
     */
    public function getFacilityById($id)
    {
        // Check if the provided ID is an integer; throw an exception if not.
        if (!is_int($id)) {
            throw new BadRequest('Invalid facility ID');
        }

        // Prepare the SQL query to fetch the facility by its ID.
        $query = 'SELECT * FROM Facility WHERE id = :id';
        // Execute the prepared query with the provided ID parameter.
        $this->db->executeQuery($query, [':id' => $id]);
        // Fetch the facility data as an associative array.
        $facility = $this->db->getStatement()->fetch(PDO::FETCH_ASSOC);

        // If no facility is found for the given ID, throw a NotFound exception.
        if (!$facility) {
            throw new NotFound('Invalid facility ID');
        }

        // Return the fetched facility data.
        return $facility;
    }

    /**
     * Retrieves location data associated with a facility from the database.
     *
     * @param int $facility_id ID of the facility
     *
     * @return array  Location data of the facility
     */
    public function getLocationByFacilityId($facility_id)
    {
        // SQL query to get location details based on the facility id
        $query = 'SELECT L.city, L.address, L.zip_code, L.country_code, L.phone_number 
              FROM Location L INNER JOIN Facility F ON F.location_id = L.id 
              WHERE F.id = :facility_id';

        // Execute the query with the facility id as a parameter
        $this->db->executeQuery($query, [':facility_id' => $facility_id]);

        // Fetch and return the location details
        return $this->db->getStatement()->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Updates the details of a facility in the database, including the facility's name, location details, and tags.
     *
     * @param  int $id    The ID of the facility to update.
     * @param  array $data  An associative array containing the new facility data.
     *                     The array must include the key 'name' with its corresponding string value.
     *                     The 'location' key should be another associative array with the keys 'city', 'address', 'zip_code', 'country_code', and 'phone_number' and their corresponding string values.
     *                     The 'tags' key should be an array of strings.
     *
     * This function starts a database transaction. If any part of the update process fails, it rolls back the transaction.
     * Otherwise, it commits the transaction.
     *
     * If the facility data, location data, or tag data is invalid, this function throws a BadRequest exception with a message specifying the missing or invalid field.
     *
     * If the specified facility does not exist, this function throws a NotFoundException.
     *
     * If an error occurs while interacting with the database, this function throws a PDOException.
     *
     * @return void
     *
     * @throws BadRequest If the facility data, location data, or tag data is invalid.
     * @throws NotFoundException If the specified facility does not exist.
     * @throws PDOException If an error occurs while interacting with the database.
     */
    public function updateFacility($id, $data)
    {
        // Start a database transaction
        $this->db->beginTransaction();

        try {
            // Validate the facility data
            if (!isset($data['name']) || !is_string($data['name'])) {
                throw new BadRequest("Invalid facility data: missing or invalid 'name'");
            }

            // Update the facility
            $query = 'UPDATE Facility SET name = :name WHERE id = :id';
            $this->db->executeQuery($query, [':name' => $data['name'], ':id' => $id]);

            // Validate the location data
            $required_fields = ['city', 'address', 'zip_code', 'country_code', 'phone_number'];
            foreach ($required_fields as $field) {
                if (!isset($data['location'][$field]) || !is_string($data['location'][$field])) {
                    throw new BadRequest("Invalid location data: missing or invalid '$field'");
                }
            }

            // Get the location_id for the current facility
            $query = 'SELECT location_id FROM Facility WHERE id = :id';
            $this->db->executeQuery($query, [':id' => $id]);
            $location_id = $this->db->getStatement()->fetchColumn();

            // Update the location
            $query = 'UPDATE Location SET city = :city, address = :address, zip_code = :zip_code, country_code = :country_code, phone_number = :phone_number WHERE id = :id';
            $this->db->executeQuery($query, array_merge([':id' => $location_id], $data['location']));

            // Update the tags
            // Remove old tags first
            $query = 'DELETE FROM Facility_Tag WHERE facility_id = :facility_id';
            $this->db->executeQuery($query, [':facility_id' => $id]);

            // Then add new tags
            $tags = $data['tags'];
            foreach ($tags as $tag) {
                // Validate the tag data
                if (!is_string($tag)) {
                    throw new BadRequest('Invalid tag data');
                }

                // Check if the tag already exists
                $query = 'SELECT id FROM Tag WHERE name = :name';
                $this->db->executeQuery($query, [':name' => $tag]);
                $tag_id = $this->db->getStatement()->fetchColumn();

                // If the tag does not exist, create it
                if (!$tag_id) {
                    $query = 'INSERT INTO Tag (name) VALUES (:name)';
                    $this->db->executeQuery($query, [':name' => $tag]);
                    $tag_id = $this->db->getLastInsertedId();
                }

                // Then, link the tag to the facility
                $query = 'INSERT INTO Facility_Tag (facility_id, tag_id) VALUES (:facility_id, :tag_id)';
                $this->db->executeQuery($query, [':facility_id' => $id, ':tag_id' => $tag_id]);
            }

            // Delete any unused tags
            $query = 'DELETE t FROM Tag t LEFT JOIN Facility_Tag ft ON ft.tag_id = t.id WHERE ft.tag_id IS NULL';
            $this->db->executeQuery($query);

            // If everything was successful, commit the transaction
            $this->db->commit();
        } catch (Exception $e) {
            // If an error occurred, rollback the transaction
            $this->db->rollback();
            // And re-throw the exception to be handled elsewhere
            throw $e;
        }
    }

    /**
     * Deletes a facility, its associated location, and tags from the database.
     *
     * @param int $id ID of the facility to delete
     *
     * @throws Exception If an error occurs during the database transaction
     */
    public function deleteFacility($id)

    {
        $facility = $this->getFacilityById($id);

        // Start a database transaction
        $this->db->beginTransaction();

        try {
            // Get the tags associated with the facility
            $tags = $this->getTagsByFacilityId($id);

            // Delete the tags from the Facility_Tag table
            $query = 'DELETE FROM Facility_Tag WHERE facility_id = :id';
            $this->db->executeQuery($query, [':id' => $id]);

            // Delete the facility from the database
            $query = 'DELETE FROM Facility WHERE id = :id';
            $this->db->executeQuery($query, [':id' => $id]);

            // Get the location_id for the current facility
            $query = 'SELECT location_id FROM Facility WHERE id = :id';
            $this->db->executeQuery($query, [':id' => $id]);
            $location_id = $this->db->getStatement()->fetchColumn();

            // Delete the location associated with the facility
            $query = 'DELETE FROM Location WHERE id = :id';
            $this->db->executeQuery($query, [':id' => $location_id]);

            // Check each tag to see if it's being used by any other facilities
            foreach ($tags as $tag) {
                $tag_id = $tag['id'];
                $query = 'SELECT COUNT(*) FROM Facility_Tag WHERE tag_id = :tag_id';
                $this->db->executeQuery($query, [':tag_id' => $tag_id]);
                $count = $this->db->getStatement()->fetchColumn();

                // If the tag is not used by any other facilities, delete it
                if ($count == 0) {
                    $query = 'DELETE FROM Tag WHERE id = :tag_id';
                    $this->db->executeQuery($query, [':tag_id' => $tag_id]);
                }
            }

            // If everything was successful, commit the transaction
            $this->db->commit();
        } catch (Exception $e) {
            // If an error occurred, rollback the transaction
            $this->db->rollback();
            // And re-throw the exception to be handled elsewhere
            throw $e;
        }
    }

    /**
     * Retrieves all facilities, their associated locations, and tags from the database.
     *
     * @return array  List of all facilities, each with their location and tags
     */
    public function getAllFacilities()
    {
        // Query to get all facilities
        $query = 'SELECT * FROM Facility';
        $this->db->executeQuery($query);
        $facilities = $this->db->getStatement()->fetchAll(PDO::FETCH_ASSOC);

        // For each facility, get its tags and location and add them to the facility array
        foreach ($facilities as $key => $facility) {
            // Get the location for this facility
            $query = 'SELECT * FROM Location WHERE id = :id';
            $this->db->executeQuery($query, [':id' => $facility['location_id']]);
            $location = $this->db->getStatement()->fetch(PDO::FETCH_ASSOC);

            // Add the location to the facility
            $facilities[$key]['location'] = $location;

            // Get the tags for this facility
            $query = '
            SELECT Tag.name
            FROM Tag
            INNER JOIN Facility_Tag ON Tag.id = Facility_Tag.tag_id
            WHERE Facility_Tag.facility_id = :id
        ';
            $this->db->executeQuery($query, [':id' => $facility['id']]);
            $tags = $this->db->getStatement()->fetchAll(PDO::FETCH_COLUMN);

            // Add the tags to the facility
            $facilities[$key]['tags'] = $tags;

            // Remove location_id from each facility
            unset($facilities[$key]['location_id']);
        }

        return $facilities;
    }

    /**
     * Searches facilities, their associated locations, and tags based on a query.
     *
     * @param string $query The search query string. This function will return facilities that have a name, tag, or city matching this query.
     * @param int $page The page number for pagination. This is used together with the $size parameter to determine the range of results to return.
     * @param int $size The number of results to return per page. This is used together with the $page parameter for pagination.
     *
     * The search is performed using a LIKE SQL query, so partial matches are included. For example, if the query is "ams", a facility with the city "Amsterdam" would be a match.
     *
     * The returned results include the total number of matches (not just the number of results on the current page), and for each facility, the facility's details, its location details, and its tags.
     *
     * If no matches are found, this function returns null.
     *
     * @return array|null An array of matching facilities, with each facility represented as an associative array including 'id', 'facility_name', 'location', and 'tags'. The 'location' is an associative array of location details, and 'tags' is an array of tag names. The array also includes a 'total' key with the total number of matches. If no matches are found, this function returns null.
     */
    public function searchFacilities($query, $page = 1, $size = 10)
    {
        // Ensure $page and $size are integers to avoid SQL injection
        $offset = intval(($page - 1) * $size);
        $size = intval($size);

        // SQL query to search facilities, tags, and locations
        $sql = "SELECT f.id, f.name as facility_name, l.city, l.address, l.zip_code, l.country_code, l.phone_number
        FROM Facility f
        LEFT JOIN Location l ON f.location_id = l.id
        WHERE f.name LIKE :query
        OR l.city LIKE :query
        OR EXISTS (
            SELECT 1 FROM Facility_Tag ft
            INNER JOIN Tag t ON ft.tag_id = t.id
            WHERE ft.facility_id = f.id AND t.name LIKE :query
        )
        GROUP BY f.id
        LIMIT $size OFFSET $offset"; // directly inject values into SQL string

        // Execute the query
        $this->db->executeQuery($sql, [':query' => "%$query%"]);

        // Fetch the results
        $results = $this->db->getStatement()->fetchAll(PDO::FETCH_ASSOC);

        // Check if any results were found
        if (!$results) {
            return null;
        }

        // For each facility, format the tags and location
        foreach ($results as $key => $result) {
            // Get the tags for this facility
            $sql = "SELECT t.name
            FROM Tag t
            INNER JOIN Facility_Tag ft ON t.id = ft.tag_id
            WHERE ft.facility_id = :id";
            $this->db->executeQuery($sql, [':id' => $result['id']]);
            $tags = $this->db->getStatement()->fetchAll(PDO::FETCH_COLUMN);

            // Add the tags to the facility
            $results[$key]['tags'] = $tags;

            $results[$key]['location'] = [
                'city' => $result['city'],
                'address' => $result['address'],
                'zip_code' => $result['zip_code'],
                'country_code' => $result['country_code'],
                'phone_number' => $result['phone_number']
            ];
        }

        // Count total results
        $sql = "SELECT COUNT(*) as total
            FROM Facility f
            LEFT JOIN Location l ON f.location_id = l.id
            WHERE f.name LIKE :query
            OR l.city LIKE :query
            OR EXISTS (
                SELECT 1 FROM Facility_Tag ft
                INNER JOIN Tag t ON ft.tag_id = t.id
                WHERE ft.facility_id = f.id AND t.name LIKE :query
            )";
        $this->db->executeQuery($sql, [':query' => "%$query%"]);
        $total = $this->db->getStatement()->fetch(PDO::FETCH_ASSOC)['total'];

        return [
            'total' => $total,
            'results' => $results
        ];
    }

}

