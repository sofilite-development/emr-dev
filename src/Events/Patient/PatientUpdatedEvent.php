<?php

/**
 * PatientUpdatedEvent
 *
 * This event is fired when a patient is updated so modules can
 * listen for changes in patient data and perform additional
 * processing.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Ken Chapple <ken@mi-squared.com>
 * @copyright Copyright (c) 2021 Ken Chapple <ken@mi-squared.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Events\Patient;

// require_once("../../../library/RabbitMQ/RabbitMQService.php");

// use Exception;
// use OpenEMR\Common\Uuid\UuidRegistry;
// use OpenEMR\Library\RabbitMQ\RabbitMQService;
use Symfony\Contracts\EventDispatcher\Event;

class PatientUpdatedEvent extends Event
{
    /**
     * This event is triggered after a patient has been updated, and an assoc
     * array of new patient data is passed to the event object
     */
    const EVENT_HANDLE = 'patient.updated';

    private $dataBeforeUpdate;
    private $newPatientData;

    /**
     * PatientUpdatedEvent constructor.
     * @param $dataBeforeUpdate
     * @param $newPatientData
     */
    public function __construct($dataBeforeUpdate, $newPatientData)
    {
        $this->dataBeforeUpdate = $dataBeforeUpdate;
        $this->newPatientData = $newPatientData;

        // $this->__invoke($this);
    }

    // public function __invoke(PatientUpdatedEvent $event)
    // {
    //     try {
    //         $patientData = $event->getNewPatientData();

    //         // Format the data
    //         $formattedData = array(
    //             'id' => $patientData['pid'] ?? '',
    //             'uuid' => !empty($patientData['uuid']) ? UuidRegistry::uuidToString($patientData['uuid']) : '',
    //             'firstName' => cleanUtf8($patientData['fname'] ?? ''),
    //             'lastName' => cleanUtf8($patientData['lname'] ?? ''),
    //             'middleName' => cleanUtf8($patientData['mname'] ?? ''),
    //             'email' => cleanUtf8($patientData['email'] ?? ''),
    //             'phone' => cleanUtf8($patientData['phone_home'] ?? ''),
    //             'dob' => cleanUtf8($patientData['DOB'] ?? ''),
    //             'sex' => cleanUtf8($patientData['sex'] ?? ''),
    //             'ssn' => cleanUtf8($patientData['ss'] ?? ''),
    //             'street' => cleanUtf8($patientData['street'] ?? ''),
    //             'city' => cleanUtf8($patientData['city'] ?? ''),
    //             'state' => cleanUtf8($patientData['state'] ?? ''),
    //             'zip' => cleanUtf8($patientData['postal_code'] ?? ''),
    //             'country' => cleanUtf8($patientData['country_code'] ?? '')
    //         );

    //         $message = json_encode(
    //             $formattedData,
    //             JSON_UNESCAPED_UNICODE |
    //             JSON_UNESCAPED_SLASHES |
    //             JSON_PARTIAL_OUTPUT_ON_ERROR
    //         );

    //         if ($message === false) {
    //             error_log("Patient Data : " . print_r($formattedData, true));
    //             throw new Exception("Failed to encode Patient data: " . json_last_error_msg());
    //         }

    //         $rabbitMQ = new RabbitMQService();
    //         $rabbitMQ->sendMessage($message, 'patient_updated');
    //         $rabbitMQ->close();

    //     } catch (\Exception $e) {
    //         error_log("Failed to send Updated Patient Data to rabbitmq: " . $e->getMessage());
    //         error_log("Patient Data that failed: " . print_r($formattedData ?? [], true));
    //     }
    // }

    /**
     * @return mixed
     */
    public function getDataBeforeUpdate()
    {
        return $this->dataBeforeUpdate;
    }

    /**
     * @param mixed $dataBeforeUpdate
     */
    public function setDataBeforeUpdate($dataBeforeUpdate): void
    {
        $this->dataBeforeUpdate = $dataBeforeUpdate;
    }

    /**
     * @return mixed
     */
    public function getNewPatientData()
    {
        return $this->newPatientData;
    }

    /**
     * @param mixed $newPatientData
     */
    public function setNewPatientData($newPatientData): void
    {
        $this->newPatientData = $newPatientData;
    }
}
