<?php

namespace Services\Suppliers\Interfaces;

/**
 * Interface PhoneNumberValidationServiceInterface
 */
interface PhoneNumberValidationServiceInterface
{
    const STATUS_CONNECTED = 1;
    const STATUS_DISCONNECTED = 2;
    const STATUS_PENDING = 3;
    const STATUS_INDETERMINANT = 4;

    /**
     * @param $phoneNumber
     * @return string
     */
    public function postNumber($phoneNumber);

    /**
     * @param $validationId
     * @return string
     */
    public function retrieveResult($validationId);
}
