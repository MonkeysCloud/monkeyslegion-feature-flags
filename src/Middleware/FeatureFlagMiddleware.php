<?php
declare(strict_types=1);

namespace MonkeysLegion\FeatureFlags\Middleware;

use MonkeysLegion\FeatureFlags\Feature;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * MonkeysLegion Framework — Feature Flags Package
 *
 * Middleware that checks feature flags for route access.
 *
 * When a flag is inactive, returns 404 or redirects.
 *
 * @copyright 2026 MonKeysCloud Team
 * @license   MIT
 */
final class FeatureFlagMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        // Get the flag name from request attribute (set by route matcher)
        $flagName = $request->getAttribute('feature_flag');
        $redirect = $request->getAttribute('feature_flag_redirect');
        $errorMessage = $request->getAttribute('feature_flag_error');

        if ($flagName === null) {
            return $handler->handle($request);
        }

        if (Feature::isActive((string) $flagName)) {
            return $handler->handle($request);
        }

        // Flag is inactive — deny access
        if ($redirect !== null) {
            return new \MonkeysLegion\Http\Message\Response(
                \MonkeysLegion\Http\Message\Stream::empty(),
                302,
                ['Location' => $redirect],
            );
        }

        $message = $errorMessage ?? 'This feature is not available.';
        return \MonkeysLegion\Http\Message\Response::json(
            ['error' => $message],
            404,
        );
    }
}
