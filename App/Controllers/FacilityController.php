<?php

namespace App\Controllers;

use App\Plugins\Http\Response as Status;
use App\Plugins\Http\Exceptions;
use PDO;
use PDOException;

class FacilityController extends BaseController {

    public function create() {
        // Get the Content-Type of the request
        $content_type = $_SERVER['CONTENT_TYPE'] ?? '';

        // Get the request data
        $data = [];
        if (strpos($content_type, 'application/json') !== false) {
            // Handle JSON data
            $data = json_decode(file_get_contents('php://input'), true);
        } elseif (strpos($content_type, 'application/x-www-form-urlencoded') !== false) {
            // Handle x-www-form-urlencoded data
            $data = $_POST;
        } elseif (strpos($content_type, 'multipart/form-data') !== false) {
            // Handle form-data
            $data = $_POST;
            // If you need to handle file uploads, you will also need to check the $_FILES superglobal
        }

        // Validate input data
        if (!isset($data['name'], $data['location'], $data['tags'])) {
            // Send a response
            return (new Status\BadRequest(['message' => 'Invalid input data']))->send();
        }

        $name = $data['name'];
        $location_data = $data['location'];
        $tags = $data['tags'];

        try {
            // Create a new location in the database
            $query = 'INSERT INTO Location (city, address, zip_code, country_code, phone_number) VALUES (:city, :address, :zip_code, :country_code, :phone_number)';
            $this->db->executeQuery($query, $location_data);

            // Get the ID of the newly created location
            $location_id = $this->db->getLastInsertedId();

            // Create a new facility in the database
            $query = 'INSERT INTO Facility (name, location_id, creation_date) VALUES (:name, :location_id, NOW())';
            $this->db->executeQuery($query, [':name' => $name, ':location_id' => $location_id]);

            // Get the ID of the newly created facility
            $facility_id = $this->db->getLastInsertedId();

            // Insert the tags
            foreach ($tags as $tag) {
                // First, check if the tag already exists
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
                $this->db->executeQuery($query, [':facility_id' => $facility_id, ':tag_id' => $tag_id]);
            }
        } catch (PDOException $e) {
            // Handle exception
            // Log the error message and send a response
            error_log($e->getMessage());
            return (new Status\InternalServerError(['message' => 'An error occurred while creating the facility']))->send();
        }

        // Send a response
        return (new Status\Created(['message' => 'Facility created successfully']))->send();
    }

    public function readAll() {
        // Query the database for all facilities
        $query = 'SELECT * FROM Facility';
        $this->db->executeQuery($query);

        // Fetch the results
        $facilities = $this->db->getStatement()->fetchAll(PDO::FETCH_ASSOC);

        // For each facility, add the location and tags to the result
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
        }

        // Send a response
        return (new Status\Ok($facilities))->send();
    }

    public function read($id) {
        // Query the database for the facility with the given ID, including location and tags information
        $query = 'SELECT f.id, f.name, f.creation_date, f.location_id, l.city, l.address, l.zip_code, l.country_code, l.phone_number,
                     GROUP_CONCAT(t.name) as tags
              FROM Facility f
              JOIN Location l ON f.location_id = l.id
              LEFT JOIN Facility_Tag ft ON f.id = ft.facility_id
              LEFT JOIN Tag t ON ft.tag_id = t.id
              WHERE f.id = :id
              GROUP BY f.id';

        $this->db->executeQuery($query, [':id' => $id]);

        // Fetch the result
        $facility = $this->db->getStatement()->fetch(PDO::FETCH_ASSOC);

        // If no facility was found, return a 404 Not Found status
        if (!$facility) {
            return (new Status\NotFound(['message' => 'Facility not found']))->send();
        }

        // Send a response
        return (new Status\Ok($facility))->send();
    }

    public function update($id) {
        // Check if the facility exists
        $query = 'SELECT * FROM Facility WHERE id = :id';
        $this->db->executeQuery($query, [':id' => $id]);
        $facility = $this->db->getStatement()->fetch(PDO::FETCH_ASSOC);

        // If the facility does not exist, return a 404 Not Found status
        if (!$facility) {
            return (new Status\NotFound(['message' => 'Facility not found']))->send();
        }

        // Get the request data
        $data = json_decode(file_get_contents('php://input'), true);

        // Validate the data (you might want to add more robust validation here)
        if (!isset($data['name']) || !isset($data['location']) || !isset($data['tags'])) {
            return (new Status\BadRequest(['message' => 'Invalid input data']))->send();
        }

        // Update the facility in the database
        $query = 'UPDATE Facility SET name = :name WHERE id = :id';
        $this->db->executeQuery($query, [':name' => $data['name'], ':id' => $id]);

        // Update the location in the database
        $location_data = $data['location'];
        $query = 'UPDATE Location
              SET city = :city, address = :address, zip_code = :zip_code,
                  country_code = :country_code, phone_number = :phone_number
              WHERE id = :location_id';
        $this->db->executeQuery($query, [
            ':city' => $location_data['city'],
            ':address' => $location_data['address'],
            ':zip_code' => $location_data['zip_code'],
            ':country_code' => $location_data['country_code'],
            ':phone_number' => $location_data['phone_number'],
            ':location_id' => $facility['location_id']
        ]);

        // Update the tags in the database
        // Remove old tags first
        $query = 'DELETE FROM Facility_Tag WHERE facility_id = :facility_id';
        $this->db->executeQuery($query, [':facility_id' => $id]);

        // Then add new tags
        $tags = $data['tags'];
        foreach ($tags as $tag) {
            // First, check if the tag already exists
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

        // Send a response
        return (new Status\Ok(['message' => 'Facility updated successfully']))->send();
    }

    public function delete($id) {
        // Check if the facility with the given ID exists in the database
        $query = 'SELECT id FROM Facility WHERE id = :id';
        $this->db->executeQuery($query, [':id' => $id]);
        $facility = $this->db->getStatement()->fetch(PDO::FETCH_ASSOC);

        // If the facility does not exist, return a 404 Not Found status
        if (!$facility) {
            return (new Status\NotFound(['message' => 'Facility not found']))->send();
        }

        // Delete the tags from the Facility_Tag table
        $query = 'DELETE FROM Facility_Tag WHERE facility_id = :id';
        $this->db->executeQuery($query, [':id' => $id]);

        // Delete the facility from the database
        $query = 'DELETE FROM Facility WHERE id = :id';
        $this->db->executeQuery($query, [':id' => $id]);

        // Send a response
        return (new Status\Ok(['message' => 'Facility deleted successfully']))->send();
    }

    public function search() {
        // Get the search query, page, and size from the request data
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['query'])) {
            return (new Status\BadRequest(['message' => 'No search query provided']))->send();
        }
        $query = $data['query'];
        $page = isset($data['page']) ? max(1, $data['page']) : 1;  // default to 1 if not set
        $size = isset($data['size']) ? max(1, $data['size']) : 10;  // default to 10 if not set

        // Calculate the offset
        $offset = ($page - 1) * $size;

        // Search the Facility, Tag, and Location tables with pagination
        $sql = "SELECT f.id, f.name as facility_name, l.*, t.name as tag_name
            FROM Facility f
            JOIN Location l ON f.location_id = l.id
            LEFT JOIN Facility_Tag ft ON f.id = ft.facility_id
            LEFT JOIN Tag t ON ft.tag_id = t.id
            WHERE f.name LIKE :query
            OR t.name LIKE :query
            OR l.city LIKE :query
            LIMIT :size OFFSET :offset";
        $this->db->executeQuery($sql, [':query' => "%$query%", ':size' => $size, ':offset' => $offset]);

        // Fetch the results
        $results = $this->db->getStatement()->fetchAll(PDO::FETCH_ASSOC);

        // Check if any results were found
        if (empty($results)) {
            return (new Status\Ok(['message' => 'No facilities found matching the search query']))->send();
        }

        // Send a response
        return (new Status\Ok($results))->send();
    }

}