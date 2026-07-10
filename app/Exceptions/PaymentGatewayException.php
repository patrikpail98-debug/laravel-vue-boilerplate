<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when the Nexi XPay gateway can't be reached or rejects a request -
 * distinct from validation errors so callers can map it to a 502 response.
 */
class PaymentGatewayException extends RuntimeException
{
}
