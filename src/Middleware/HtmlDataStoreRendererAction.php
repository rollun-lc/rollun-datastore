<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 26.01.17
 * Time: 15:11
 */

namespace rollun\datastore\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use rollun\installer\Command;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Stratigility\MiddlewareInterface;
use rollun\actionrender\Renderer\Html\HtmlRendererAction;

class HtmlDataStoreRendererAction extends HtmlRendererAction
{
    public function process(Request $request, DelegateInterface $delegate)
    {
        $data = $request->getAttribute('responseData');
        $name = $request->getAttribute('templateName');

        /** @var Response $response */
        $response = $request->getAttribute(Response::class) ?: null;
        if (!isset($response)) {
            $status = 200;
            $headers = [];
        } else {
            $status = $response->getStatusCode();
            $headers = $response->getHeaders();
        }
        $page = $this->templateRenderer->render($name, ['data' => $data]);
        $response = new HtmlResponse($page, $status);
        foreach ($headers as $header => $value) {
            $response = $response->withHeader($header, $value);
        }
        $request = $request->withAttribute(
            Response::class,
            $response
        );

        $response = $delegate->process($request);

        return $response;
    }
}
