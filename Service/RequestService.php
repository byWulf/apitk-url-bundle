<?php

declare(strict_types=1);

namespace Shopping\ApiTKUrlBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class RequestService.
 *
 * @package Shopping\ApiTKUrlBundle\Service
 */
class RequestService
{
    /**
     * @param RequestStack $requestStack
     *
     * @return Request
     */
    public function getMainRequest(RequestStack $requestStack): Request
    {
        return method_exists($requestStack, 'getMainRequest')
            ? $requestStack->getMainRequest() ?? Request::createFromGlobals() // symfony >= 5.3
            : $requestStack->getMasterRequest() ?? Request::createFromGlobals(); // symfony <= 5.2 @phpstan-ignore-line
    }
}
