<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 26.01.17
 * Time: 15:11
 */

namespace rollun\datastore\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Stratigility\MiddlewareInterface;
use rollun\actionrender\Renderer\Html\HtmlRendererAction;

class HtmlDataStoreRendererAction extends HtmlRendererAction
{
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        $data = $request->getAttribute('responseData');
        $name = $request->getAttribute('templateName');
        $status = $request->getAttribute('status') ?: 200;

        $request = $request->withAttribute(
            Response::class,
            new HtmlResponse($this->templateRenderer->render($name, ['data' => $data]), $status)
        );

        if (isset($out)) {
            return $out($request, $response);
        }

        return $response;
    }
}
