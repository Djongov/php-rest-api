<?php declare(strict_types=1);

// Path: Controllers/Api/Firewall.php

// Called in /api/firewall in /routes/firewall.php

// Responsible for handling the CRUD api calls for the firewall table in the database and returning the appropriate api json response

namespace Controllers;

use Models\AccessLog as AccessLogModel;
use App\Exceptions\AccessLogException;

class AccessLog
{
    public string $table = 'api_access_log';
    /**
     * Get an IP or all IPs from the firewall table and return them as a json response
     * @category   Controller - Firewall
     * @author     Original Author <djongov@gamerz-bg.com>
     * @param      string $ip the ip in normal or CIDR notation. If empty, returns all IPs
     * @return     string json api response
     * @throws     AccessLogException
     */
    public function get(?string $ip, ?string $sort = null, ?int $limit = null, ?string $orderBy = null) : array
    {
        // Let's do some validation here
        $sort = (!$sort) ? 'ASC' : strtoupper($sort);
        $sortValues = ['ASC', 'DESC'];
        if (!in_array($sort, $sortValues)) {
            return ['error' => 'Invalid sort value', 'status' => 400];
        }
        if ($orderBy === null) {
            $orderBy = 'id';
        } else {
            if (!in_array($orderBy, (new AccessLogModel())->getColumns($this->table))) {
                return ['error' => 'Invalid orderBy value', 'status' => 400];
            }
        }
        $firewall = new AccessLogModel();
        $response = [];
        try {
            $response = ['data' => $firewall->get($ip, $sort, $limit, $orderBy), 'status' => 200];
        } catch (AccessLogException $e) {
            $response = ['error' => $e->getMessage(), 'status' => $e->getCode()];
        } catch (\Exception $e) {
            if (ERROR_VERBOSE) {
                $response = ['error' => $e->getMessage(), 'status' => $e->getCode()];
            } else {
                $response = ['error' => 'An unexpected error occurred', 'status' => 500];
            }
        }
        return $response;
    }
    public function add(array $data) : array
    {
        $response = [];
        // Filter for invalid parameters
        $createAcceptedParams = ['request_id', 'api_key', 'status_code'];
        foreach ($data as $key => $value) {
            if (!in_array($key, $createAcceptedParams)) {
                return ['error' => 'Invalid parameter ' . $key, 'status' => 400];
            }
        }
        // Make sure that the required parameters are passed
        $requiredParams = ['request_id', 'api_key', 'status_code'];
        // Check if the required parameters are passed
        foreach ($requiredParams as $name) {
            if (!array_key_exists($name, $data)) {
                return ['error' => 'missing parameter \'' . $name . '\'', 'status' =>  400];
            }
            // need to check if the parameter is empty but not use empty() as it returns incorrect for value 0
            if ($data[$name] === null || $data[$name] === '') {
                return ['error' => 'parameter \'' . $name . '\' cannot be empty', 'status' =>  400];
            }
        }

        // Let's add the rest of the parameters to the data for which we don't need user input
        $data['client_ip'] = currentIP();
        $data['user_agent'] = currentBrowser();
        $data['uri'] = currentUrl();
        $data['method'] = $_SERVER['REQUEST_METHOD'];

        $firewall = new AccessLogModel();
        try {
            $returnData = [];
            $responseFromModel = $firewall->add($data);
            if ($responseFromModel) {
                $response = ['data' => $returnData, 'status' => 200];
            } else {
                $response = ['error' => 'error', 'status' => 400];
            }
        } catch (AccessLogException $e) {
            $response = ['error' => $e->getMessage(), 'status' => $e->getCode()];
        } catch (\Exception $e) {
            if (ERROR_VERBOSE) {
                $response = ['error' => $e->getMessage(), 'status' => $e->getCode()];
            } else {
                $response = ['error' => 'An unexpected error occurred', 'status' => 500];
            }
        }

        return $response;
    }
    /**
     * Deletes an IP from the firewall table. If the IP does not exist, throws an exception, otherwise deletes the IP and returns a json response
     * @category   Controller - Firewall
     * @author     Original Author <djongov@gamerz-bg.com>
     * @param      int $id the id of the IP
     * @param      string $deletedBy the user who deletes the IP, for logging purposes
     * @return     string json api response
     * @throws     AccessLogException
     */
    public function delete(int $id, string $deletedBy) : array
    {
        $response = [];
        $firewall = new AccessLogModel();
        try {
            $delete = $firewall->delete($id, $deletedBy);
            if ($delete) {
                $response = ['data' => 'ip with id ' . $id . ' deleted successfully', 'status' => 204];
            } else {
                $response = ['error' => 'delete was not successful, either there was nothing to be deleted or there was an error', 'status' => 409];
            }
        } catch (AccessLogException $e) {
            $response = ['error' => $e->getMessage(), 'status' => 400];
        } catch (\Exception $e) {
            if (ERROR_VERBOSE) {
                $response = ['error' => $e->getMessage(), 'status' => 400];
            } else {
                $response = ['error' => 'An unexpected error occurred', 'status' => 500];
            }
        }
        return $response;
    }
}
