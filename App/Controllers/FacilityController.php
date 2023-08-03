<?php


namespace App\Controllers;

use App\Models\FacilityModel;
use App\Plugins\Http\Response as Status;
use App\Plugins\Http\Exceptions\BadRequest;
use PDO;
use App\Plugins\Http\Response\NoContent;
use PDOException;

/**
 * Class FacilityController
 *
 * Handles HTTP requests for operations related to facilities.
 */
class FacilityController extends BaseController
{
    /**
     * @var FacilityModel Holds the facility model instance.
     */
    private FacilityModel $facilityModel;

    /**
     * FacilityController constructor.
     *
     * @param FacilityModel $facilityModel An instance of the FacilityModel.
     */
    public function __construct(FacilityModel $facilityModel)
    {
        // Initialize the FacilityModel instance
        $this->facilityModel = $facilityModel;
    }

    /**
     * Extracts and validates the request data.
     *
     * @return array Request data
     *
     * @throws BadRequest If the input data is invalid.
     */
    private function extractData(): array
    {
        // Get the content type of the request, if available.
        $content_type = $_SERVER['CONTENT_TYPE'] ?? '';

        // Initialize an empty array to store the extracted data.
        $data = [];

        // Extract and validate the data based on the content type of the request.
        if (strpos($content_type, 'application/json') !== false) {
            // Extract JSON data from the request body and decode it as an associative array.
            $data = json_decode(file_get_contents('php://input'), true);
        } elseif (strpos($content_type, 'application/x-www-form-urlencoded') !== false) {
            // If the content type is 'application/x-www-form-urlencoded', extract data from $_POST.
            $data = $_POST;
        } elseif (strpos($content_type, 'multipart/form-data') !== false) {
            // If the content type is 'multipart/form-data', extract data from $_POST.
            $data = $_POST;
        }

        // Perform additional validation on the extracted data.
        if (
            !isset($data['name'], $data['location']) ||
            !is_string($data['name']) ||
            !is_array($data['location']) ||
            array_diff_key($data['location'], array_flip(['city', 'address', 'zip_code', 'country_code', 'phone_number'])) ||
            (isset($data['tags']) && !is_array($data['tags']))
        ) {
            // If the data is invalid or incomplete, throw a BadRequest exception.
            throw new BadRequest('Invalid input data');
        }

        // If 'tags' key is not set in the data, set it to an empty array.
        $data['tags'] = $data['tags'] ?? [];

        // Return the validated data.
        return $data;
    }

    /**
     * Handles the creation of a new facility.
     *
     * @return Status\Created|Status\InternalServerError|Status\BadRequest Response status
     */
    public function create()
    {
        // Extract and validate the request data
        try {
            $data = $this->extractData(); // Assuming this method calls the extractData() function to get the validated data.
        } catch (BadRequest $e) {
            // If the data extraction/validation fails, return a BadRequest response with the error message.
            return (new Status\BadRequest(['message' => $e->getMessage()]))->send();
        }

        // Extract data from the validated array.
        $name = $data['name'];
        $location_data = $data['location'];
        $tags = $data['tags'];

        // Check if a facility with the same name already exists, return BadRequest response if it does.
        if ($this->facilityModel->checkFacilityExists($name)) {
            return (new Status\BadRequest(['message' => 'A facility with this name already exists']))->send();
        }

        // Create the facility using the FacilityModel and handle tag creation and association.
        try {
            // Create the location first and get its ID.
            $location_id = $this->facilityModel->createLocation($location_data);

            // Create the facility and get its ID.
            $facility_id = $this->facilityModel->createFacility($name, $location_id);

            // Iterate over the tags, create each tag, and associate it with the facility.
            foreach ($tags as $tag) {
                $tag_id = $this->facilityModel->createTag($tag);
                $this->facilityModel->createFacilityTag($facility_id, $tag_id);
            }
        } catch (PDOException $e) {
            // If any database-related error occurs during creation, log the error and return an InternalServerError response.
            error_log($e->getMessage());
            return (new Status\InternalServerError(['message' => 'An error occurred while creating the facility', 'error' => $e->getMessage()]))->send();
        }

        // Return the appropriate response status (Created) indicating successful facility creation.
        return (new Status\Created(['message' => 'Facility created successfully']))->send();
    }

    /**
     * Handles the retrieval of a facility by its ID.
     *
     * @param string $id ID of the facility to retrieve
     *
     * @return Status\Ok|Status\NotFound Response status
     */
    public function read($id)
    {
        $id = (int)$id; // Cast $id to an integer (assuming it was passed as a string).

        // Get the facility details from the FacilityModel based on the provided ID.
        $facility = $this->facilityModel->getFacilityById($id);

        // If no facility is found with the given ID, throw a NotFound exception.
        if (!$facility) {
            throw new Exceptions\NotFound('Facility not found');
        }

        // Retrieve the tags and location details for the facility using the FacilityModel.
        $tags = $this->facilityModel->getTagsByFacilityId($id);
        $location = $this->facilityModel->getLocationByFacilityId($id);

        // Include the tags and location details in the facility array.
        $facility['tags'] = $tags;
        $facility['location'] = $location;

        // Remove the 'location_id' key from the facility array if it is not needed in the response.
        unset($facility['location_id']);

        // Return the facility details as a JSON response with status code 200 (OK).
        return (new Status\Ok($facility))->send();
    }

    /**
     * Handles the update of a facility by its ID.
     *
     * @param string $id ID of the facility to update
     *
     * @return Status\Ok|Status\NotFound|Status\InternalServerError|Status\BadRequest Response status
     */
    public function update($id)
    {
        try {
            // Extract the request data like in the create() method.
            $data = $this->extractData();
        } catch (Exceptions\BadRequest $e) {
            // If the data extraction/validation fails, return a BadRequest response with the error message.
            return (new Status\BadRequest(['message' => $e->getMessage()]))->send();
        }

        // Check if the facility exists based on the provided ID.
        $facility = $this->facilityModel->getFacilityById($id);

        // If no facility is found with the given ID, return a NotFound response.
        if (!$facility) {
            return (new Status\NotFound(['message' => 'Facility not found']))->send();
        }

        try {
            // Update the facility and its associated location and tags using the FacilityModel.
            $this->facilityModel->updateFacility($id, $data);
        } catch (PDOException $e) {
            // If any database-related error occurs during the update, log the error and return an InternalServerError response.
            error_log($e->getMessage());
            return (new Status\InternalServerError(['message' => 'An error occurred while updating the facility', 'error' => $e->getMessage()]))->send();
        }

        // Fetch the updated facility details and associated tags using the FacilityModel.
        $updatedFacility = $this->facilityModel->getFacilityById($id);
        $updatedTags = $this->facilityModel->getTagsByFacilityId($id);

        // Include the updated tags in the response.
        $updatedFacility['tags'] = $updatedTags;

        // Return the updated facility details as a JSON response with status code 200 (OK).
        return (new Status\Ok(['message' => 'Facility updated successfully', 'facility' => $updatedFacility]))->send();
    }

    /**
     * Handles the deletion of a facility by its ID.
     *
     * @param string $id ID of the facility to delete
     *
     * @return NoContent|Status\NotFound|Status\BadRequest Response status
     */
    public function delete($id)
    {
        $id = (int)$id; // Cast $id to an integer (assuming it was passed as a string).

        // Delete the facility using the FacilityModel.
        try {
            $this->facilityModel->deleteFacility($id);
        } catch (Exceptions\BadRequest $e) {
            // If the request data is invalid, return a BadRequest response with the error message.
            return (new Status\BadRequest(['message' => $e->getMessage()]))->send();
        } catch (Exceptions\NotFound $e) {
            // If the facility with the given ID is not found, return a NotFound response with the error message.
            return (new Status\NotFound(['message' => $e->getMessage()]))->send();
        }

        // If the deletion is successful, return a NoContent response (status code 204).
        return (new NoContent())->send();
    }

    /**
     * Handles the retrieval of all facilities.
     *
     * @return Status\Ok Response status
     */
    public function readAll()
    {
        // Retrieve all facilities using the FacilityModel
        $facilities = $this->facilityModel->getAllFacilities();

        // Return the facilities data, or a message if no facilities were found
        if (!$facilities) {
            return (new Status\Ok(['message' => 'No facilities found']))->send();
        }

        return (new Status\Ok($facilities))->send();
    }

    /**
     * Handles the search for facilities based on a query.
     *
     * @return Status\Ok Response status
     */
    public function search()
    {
        // Get the search query, page, and size from the request parameters
        $query = $_GET['query'] ?? '';
        $page = isset($_GET['page']) ? max(1, $_GET['page']) : 1;  // default to 1 if not set
        $size = isset($_GET['size']) ? max(1, $_GET['size']) : 10;  // default to 10 if not set

        // Search the facilities
        $facilities = $this->facilityModel->searchFacilities($query, $page, $size);

        // If there are no facilities, return a message
        if (empty($facilities)) {
            return (new Status\Ok(['message' => 'No facilities found']))->send();
        }

        return (new Status\Ok($facilities))->send();
    }

}
