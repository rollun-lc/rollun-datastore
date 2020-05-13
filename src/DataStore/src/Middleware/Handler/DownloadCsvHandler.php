<?php

namespace rollun\datastore\Middleware\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Xiag\Rql\Parser\Query;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

/**
 * Class DownloadCsvHandler
 *
 * @author r.ratsun <r.ratsun.rollun@gmail.com>
 */
class DownloadCsvHandler extends AbstractHandler
{
    const HEADER = 'download';
    const DELIMITER = ',';
    const ENCLOSURE = '"';
    const ESCAPE_CHAR = '\\';

    /**
     * @inheritDoc
     */
    public function canHandle(ServerRequestInterface $request): bool
    {
        if ($request->getMethod() == 'GET') {
            foreach ($request->getHeader(self::HEADER) as $item) {
                if ($item == 'csv') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Query $rqlQuery */
        $rqlQuery = $request->getAttribute('rqlQueryObject');

        $items = $this->dataStore->query($rqlQuery);

        // create csv file
        $file = fopen('php://memory', 'w');
        foreach ($items as $line) {
            fputcsv($file, $line, self::DELIMITER, self::ENCLOSURE, self::ESCAPE_CHAR);
        }

        // set pointer to the beginning
        fseek($file, 0);

        // create file name
        $fileName = explode("/", $request->getUri()->getPath());
        $fileName = array_pop($fileName) . '.csv';

        $body = new Stream($file);

        $response = (new Response())
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename=' . $fileName)
            ->withHeader('Content-Transfer-Encoding', 'Binary')
            ->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Pragma', 'public')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate')
            ->withBody($body)
            ->withHeader('Content-Length', "{$body->getSize()}");

        return $response;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->canHandle($request)) {
            return $this->handle($request);
        }

        return $handler->handle($request);
    }
}
