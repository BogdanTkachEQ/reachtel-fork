<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Http;

use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * Class Request
 */
class Request extends HttpRequest
{
    /**
     * @param array $query
     * @param array $request
     * @param array $attributes
     * @param array $cookies
     * @param array $files
     * @param array $server
     * @param null  $content
     */
    public function initialize(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null
    ) {
        parent::initialize($query, $request, $attributes, $cookies, $files, $server, $content);

        // Symfony request component creates header from $_SERVER which may not have the Authorization header.
        // This is a work around instead of updating htaccess that symfony suggests.
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $this->headers->set('Authorization', $headers['Authorization']);
        }
    }
}
