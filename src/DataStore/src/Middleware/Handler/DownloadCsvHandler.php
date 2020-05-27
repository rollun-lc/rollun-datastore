<?php

namespace rollun\datastore\Middleware\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Xiag\Rql\Parser\Node\LimitNode;
use Xiag\Rql\Parser\Query;

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
    const LIMIT = 5000;

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
        $dataStore = $this->dataStore;

        // create file name
        $fileName = explode("/", $request->getUri()->getPath());
        $fileName = array_pop($fileName) . '.csv';

        /** @var Query $rqlQuery */
        $rqlQuery = $request->getAttribute('rqlQueryObject');

        /**
         *  Prepare headers
         */
        header("Content-Type: text/csv;charset=utf-8");
        header("Content-Disposition: attachment;filename=\"$fileName\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        // get the headers out immediately to show the download dialog
        flush();

        // create csv file
        $fp = fopen('php://output', 'w');

        $offset = 0;

        $items = [1];
        while (count($items) > 0) {
            $rqlQuery->setLimit(new LimitNode(self::LIMIT, $offset));
            $items = $dataStore->query($rqlQuery);

            foreach ($items as $line) {
                fputcsv($fp, $line, self::DELIMITER, self::ENCLOSURE, self::ESCAPE_CHAR);
            }
            flush();

            $offset = $offset + self::LIMIT;
        }

        fclose($fp);

        exit();
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
