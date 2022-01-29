<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\DataStore\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use rollun\datastore\Middleware\ResourceResolver;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;

class ResourceResolverTest extends BaseMiddlewareTest
{
    public function testProcessWithResourceNameInAttributeAndId()
    {
        $resourceName = 'resourceName';
        $id = 1;
        $request = new ServerRequest();
        $request = $request->withAttribute('resourceName', $resourceName);
        $request = $request->withAttribute('id', $id);

        $object = new ResourceResolver();
        $this->assertDelegateCallWithAssertionCallback(
            $this->getAssertionCallback($resourceName, $id),
            $request,
            $object
        );
    }

    public function testProcessWithResourceNameInAttributeAndPrimaryKeyValue()
    {
        $resourceName = 'resourceName';
        $primaryKeyValue = 1;
        $request = new ServerRequest();
        $request = $request->withAttribute('resourceName', $resourceName);
        $request = $request->withAttribute('primaryKeyValue', $primaryKeyValue);

        $object = new ResourceResolver();
        $this->assertDelegateCallWithAssertionCallback(
            $this->getAssertionCallback($resourceName, null),
            $request,
            $object
        );
    }

    public function testProcessWithoutResource()
    {
        $resourceName = 'resourceName';
        $primaryKeyValue = 1;
        $request = new ServerRequest();
        $uri = new Uri(ResourceResolver::BASE_PATH . "/{$resourceName}/{$primaryKeyValue}");
        $request = $request->withUri($uri);

        $object = new ResourceResolver();
        $this->assertDelegateCallWithAssertionCallback(
            $this->getAssertionCallback($resourceName, $primaryKeyValue),
            $request,
            $object
        );
    }

    public function testProcessWithEmptyPath()
    {
        $request = new ServerRequest();
        $uri = new Uri("/");
        $request = $request->withUri($uri);

        $object = new ResourceResolver();
        $this->assertDelegateCallWithAssertionCallback(
            $this->getAssertionCallback(null, null),
            $request,
            $object
        );
    }

    public function testProcessWithNoOrdinaryPath()
    {
        $resourceName = 'resourceName';
        $request = new ServerRequest();
        $uri = new Uri(ResourceResolver::BASE_PATH . "/{$resourceName}?A=2&bd=cd");
        $request = $request->withUri($uri);

        $object = new ResourceResolver();
        $this->assertDelegateCallWithAssertionCallback(
            $this->getAssertionCallback($resourceName, null),
            $request,
            $object
        );
    }

    public function testProcessWithResourceNameOnly()
    {
        $resourceName = 'resourceName';
        $request = new ServerRequest();
        $uri = new Uri(ResourceResolver::BASE_PATH . "/{$resourceName}/");
        $request = $request->withUri($uri);

        $object = new ResourceResolver();
        $this->assertDelegateCallWithAssertionCallback(
            $this->getAssertionCallback($resourceName, null),
            $request,
            $object
        );
    }

    protected function getAssertionCallback($resourceName, $primaryKeyValue)
    {
        $toString = function ($var) {
            if ($var === null) {
                return $var;
            }

            return (string)$var;
        };

        $resourceName = $toString($resourceName);
        $primaryKeyValue = $toString($primaryKeyValue);

        return function (ServerRequestInterface $request) use ($resourceName, $primaryKeyValue) {
            return $request->getAttribute('resourceName') === $resourceName
                && $request->getAttribute('primaryKeyValue') === $primaryKeyValue;
        };
    }
}
