<?php declare(strict_types=1);

// Path: Controllers/Api/Firewall.php

// Called in /api/firewall in /routes/firewall.php

// Responsible for handling the CRUD api calls for the firewall table in the database and returning the appropriate api json response

namespace Controllers;

use Models\Firewall as FirewallModel;
use App\Exceptions\FirewallException;
use Api\Response;

class Firewall
{
    public string $table = 'firewall';
    /**
     * Get the columns of the firewall table
     * @category   Controller - Firewall
     * @return     array the columns of the firewall table
     */
    public function getColumns() : array
    {
        $describeArray = (new FirewallModel())->getColumns($this->table);
        $columns = [];
        foreach ($describeArray as $column => $type) {
            array_push($columns, $column);
        }
        return $columns;
    }
    /**
     * Get an IP or all IPs from the firewall table and return them as a json response
     * @category   Controller - Firewall
     * @author     Original Author <djongov@gamerz-bg.com>
     * @param      string $ip the ip in normal or CIDR notation. If empty, returns all IPs
     * @return     string json api response
     * @throws     FirewallException
     */
    public function get(?string $ip, ?string $sort = null, ?int $limit = null, ?string $orderBy = null) : array
    {
        // Let's do some validation here
        if ($sort === null) {
            $sort = 'ASC';
        } else {
            $sort = strtoupper($sort);
        }
        $sortValues = ['ASC', 'DESC'];
        if (!in_array($sort, $sortValues)) {
            return ['error' => 'Invalid sort value', 'status' => 400];
        }
        if ($orderBy === null) {
            $orderBy = 'id';
        } else {
            if (!in_array($orderBy, $this->getColumns())) {
                return ['error' => 'Invalid orderBy value', 'status' => 400];
            }
        }
        $firewall = new FirewallModel();
        $response = [];
        try {
            $response = ['data' => $firewall->get($ip, $sort, $limit, $orderBy), 'status' => 200];
        } catch (FirewallException $e) {
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
    /**
     * Saves an IP to the firewall table. If the IP already exists or is malformed, throws an exception, otherwise saves the IP and returns a json response
     * @category   Controller - Firewall
     * @author     Original Author <djongov@gamerz-bg.com>
     * @param      string $ip the ip in normal or CIDR notation
     * @param      string $createdBy the user who creats the IP, not only for logging purposes, but also for the firewall to know who added the IP
     * @param      string $comment the comment for the IP
     * @return     string json api response
     * @throws     FirewallException
     */
    public function add(array $data) : array
    {
        $response = [];
        // Filter for invalid parameters
        $createAcceptedParams = ['cidr', 'createdBy', 'comment'];
        foreach ($data as $key => $value) {
            if (!in_array($key, $createAcceptedParams)) {
                return ['error' => 'Invalid parameter ' . $key, 'status' => 400];
            }
        }
        // Make sure that the required parameters are passed
        $requiredParams = ['cidr', 'createdBy'];
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
        // Make sure that the data is passed in this exact order - cidr, createdBy, comment
        $ip = $data['cidr'];
        $createdBy = $data['createdBy'];
        $comment = $data['comment'] ?? null;

        $firewall = new FirewallModel();
        try {
            $responseFromModel = $firewall->add($ip, $createdBy, $comment);
            if ($responseFromModel) {
                $response = ['data' => 'successfully added ip ' . $ip . ' under id ' . $responseFromModel, 'status' => 201];
            } else {
                $response = ['error' => 'error', 'status' => 400];
            }
        } catch (FirewallException $e) {
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
    /**
     * Updates an IP to the firewall table. If the IP does not exist or has unknown columns, throws an exception, otherwise updates the IP and returns a json response
     * @category   Controller - Firewall
     * @author     Original Author <djongov@gamerz-bg.com>
     * @param      array $data the data to update, must be an associative array with the column name as key and the new value as value
     * @param      int $id the id of the IP
     * @param      string $updatedBy the user who updates the IP, for logging purposes
     * @return     string json api response
     * @throws     FirewallException
     */
    public function update(array $data, int $id, string $updatedBy) : array
    {
        $firewall = new FirewallModel();
        $response = [];
        try {
            $update = $firewall->update($data, $id, $updatedBy);
            if ($update) {
                $response = ['data' => 'ip with id ' . $id . ' updated successfully with ' . json_encode($data), 'status' => 200];
            } else {
                $response = ['error' => 'update was not successful, either there was nothing to be updated or there was an error', 'status' => 409];
            }
            return $response;
        } catch (FirewallException $e) {
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
    /**
     * Deletes an IP from the firewall table. If the IP does not exist, throws an exception, otherwise deletes the IP and returns a json response
     * @category   Controller - Firewall
     * @author     Original Author <djongov@gamerz-bg.com>
     * @param      int $id the id of the IP
     * @param      string $deletedBy the user who deletes the IP, for logging purposes
     * @return     string json api response
     * @throws     FirewallException
     */
    public function delete(int $id, string $deletedBy) : array
    {
        $response = [];
        $firewall = new FirewallModel();
        try {
            $delete = $firewall->delete($id, $deletedBy);
            if ($delete) {
                $response = ['data' => 'ip with id ' . $id . ' deleted successfully', 'status' => 204];
            } else {
                $response = ['error' => 'delete was not successful, either there was nothing to be deleted or there was an error', 'status' => 409];
            }
        } catch (FirewallException $e) {
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
