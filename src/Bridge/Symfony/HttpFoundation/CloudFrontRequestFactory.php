<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Bridge\Symfony\HttpFoundation;

use Swoole\Http\Request as SwooleRequest;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

final readonly class CloudFrontRequestFactory implements RequestFactory
{
    public function __construct(private RequestFactory $decorated) {}

    /**
     * @see https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/header-caching.html#header-caching-web-protocol
     */
    public function make(SwooleRequest $request): HttpFoundationRequest
    {
        $httpFoundationRequest = $this->decorated->make($request);
        if ($httpFoundationRequest->headers->has('cloudfront_forwarded_proto')) {
            /** @var array<string>|string $cloudFrontForwardedProto */
            $cloudFrontForwardedProto = $httpFoundationRequest->headers->get('cloudfront_forwarded_proto');
            $httpFoundationRequest->headers->set('x_forwarded_proto', $cloudFrontForwardedProto);
        }

        return $httpFoundationRequest;
    }
}
