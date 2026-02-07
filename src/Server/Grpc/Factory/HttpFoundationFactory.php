<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc\Factory;

use SwooleBundle\SwooleBundle\Server\Grpc\Generated\HeaderValue;
use SwooleBundle\SwooleBundle\Server\Grpc\Generated\Psr7Request;
use SwooleBundle\SwooleBundle\Server\Grpc\Generated\Psr7Response;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

final class HttpFoundationFactory
{
    /**
     * Convert a PSR-7 Request protobuf message to a Symfony HttpFoundation Request.
     */
    public function make(Psr7Request $request): HttpFoundationRequest
    {
        // Parse the URI to extract components
        $uri = $request->getUri();
        $parsedUrl = parse_url($uri);

        // Extract query parameters from URI
        $queryString = $parsedUrl['query'] ?? '';
        parse_str($queryString, $query);

        // Determine scheme and port
        $scheme = $parsedUrl['scheme'] ?? 'http';
        $isHttps = $scheme === 'https';
        $defaultPort = $isHttps ? 443 : 80;
        $port = $parsedUrl['port'] ?? $defaultPort;

        // Build server array similar to DefaultRequestFactory
        $server = [
            'REQUEST_METHOD' => strtoupper($request->getMethod()),
            'REQUEST_URI' => $uri,
            'SERVER_PROTOCOL' => $request->getProtocolVersion() ?: 'HTTP/1.1',
            'QUERY_STRING' => $queryString,
            'REQUEST_SCHEME' => $scheme,
            'SERVER_NAME' => $parsedUrl['host'] ?? 'localhost',
            'SERVER_PORT' => (string) $port,
            'REMOTE_ADDR' => '127.0.0.1', // Default for gRPC requests
            'REMOTE_PORT' => '0',
        ];

        // Set HTTPS flag if using https scheme
        if ($isHttps) {
            $server['HTTPS'] = 'on';
        }

        // Add path if present
        if (isset($parsedUrl['path'])) {
            $server['PATH_INFO'] = $parsedUrl['path'];
            $server['SCRIPT_NAME'] = '';
        }

        // Initialize cookies array
        $cookies = [];

        // Convert headers from Psr7Request format to server variables
        foreach ($request->getHeaders() as $name => $headerValue) {
            /** @var HeaderValue $headerValue */
            $values = $headerValue->getValue(); // RepeatedField of strings
            $valueArray = [];
            foreach ($values as $value) {
                $valueArray[] = $value;
            }

            // Special handling for certain headers
            $lowerName = strtolower($name);
            if ($lowerName === 'content-type') {
                $server['CONTENT_TYPE'] = implode(', ', $valueArray);
            } elseif ($lowerName === 'content-length') {
                $server['CONTENT_LENGTH'] = implode(', ', $valueArray);
            } elseif ($lowerName === 'host') {
                // Set both HTTP_HOST and SERVER_NAME from Host header
                $hostValue = implode(', ', $valueArray);
                $server['HTTP_HOST'] = $hostValue;
                // Update SERVER_NAME if not already set from URI
                if (!isset($parsedUrl['host'])) {
                    $server['SERVER_NAME'] = explode(':', $hostValue)[0];
                }
            } elseif ($lowerName === 'cookie') {
                // Parse cookies from Cookie header
                $cookies = $this->parseCookies(implode('; ', $valueArray));
            } else {
                $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
                $server[$headerKey] = implode(', ', $valueArray);
            }
        }

        // Parse POST data from body if it's a POST/PUT/PATCH request
        $post = [];
        $body = $request->getBody();
        $method = strtoupper($request->getMethod());

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $contentType = $server['CONTENT_TYPE'] ?? '';
            if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
                parse_str($body, $post);
            } elseif (strpos($contentType, 'application/json') !== false) {
                // Keep body as-is for JSON, don't parse into $post
                $post = [];
            }
        }

        return new HttpFoundationRequest(
            $query,
            $post,
            [], // attributes - empty for now, can be populated by the application
            $cookies,
            [], // files - not supported in PSR-7 request message
            $server,
            $body,
        );
    }

    /**
     * Parse cookies from a Cookie header string.
     *
     * @param string $cookieHeader
     * @return array<string, string>
     */
    private function parseCookies(string $cookieHeader): array
    {
        $cookies = [];

        if ($cookieHeader === '') {
            return $cookies;
        }

        $pairs = explode('; ', $cookieHeader);
        foreach ($pairs as $pair) {
            $parts = explode('=', $pair, 2);
            if (count($parts) === 2) {
                [$name, $value] = $parts;
                $cookies[trim($name)] = urldecode(trim($value));
            }
        }

        return $cookies;
    }

    /**
     * Convert a Symfony HttpFoundation Response to a PSR-7 Response protobuf message.
     */
    public function convertResponse(HttpFoundationResponse $response): Psr7Response
    {
        $psr7Response = new Psr7Response();

        // Set status code
        $psr7Response->setStatusCode($response->getStatusCode());

        // Set protocol version
        $psr7Response->setProtocolVersion($response->getProtocolVersion());

        // Set reason phrase - HttpFoundation doesn't expose this directly
        // Use empty string as default to avoid issues with placeholder values like "-"
        $reasonPhrase = $response->headers->get('reason-phrase', '');
        if ($reasonPhrase === '-' || $reasonPhrase === null) {
            $reasonPhrase = '';
        }
        $psr7Response->setReasonPhrase($reasonPhrase);

        // Convert headers (exclude cookies as they're in separate handling)
        $headers = [];
        foreach ($response->headers->allPreserveCaseWithoutCookies() as $name => $values) {
            $headerValue = new HeaderValue();
            $headerValue->setValue($values);
            $headers[$name] = $headerValue;
        }

        // Add cookies as Set-Cookie headers
        foreach ($response->headers->getCookies() as $cookie) {
            $cookieHeader = $cookie->__toString();
            if (!isset($headers['Set-Cookie'])) {
                $headers['Set-Cookie'] = new HeaderValue();
                $headers['Set-Cookie']->setValue([$cookieHeader]);
            } else {
                $currentValues = iterator_to_array($headers['Set-Cookie']->getValue());
                $currentValues[] = $cookieHeader;
                $headers['Set-Cookie']->setValue($currentValues);
            }
        }

        $psr7Response->setHeaders($headers);

        // Set body content
        $content = $response->getContent();
        $psr7Response->setBody($content !== false ? $content : '');

        return $psr7Response;
    }
}
