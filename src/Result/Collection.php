<?php
declare(strict_types=1);

namespace Dakword\OData1C\Client\Result;

use ArrayIterator;
use Countable;
use GuzzleHttp\Psr7\Request as HttpRequest;
use GuzzleHttp\Psr7\Response as HttpResponse;
use IteratorAggregate;
use Traversable;

class Collection extends AbstractResult implements IteratorAggregate, Countable
{
    private $values = [];
    private int $totalCount;

    public function __construct(HttpRequest $request, HttpResponse $response)
    {
        parent::__construct($request, $response);

        $json = $this->getJsonContent();

        if (property_exists($json, 'odata.metadata')) {
            $this->odataMetadata = $json->{'odata.metadata'};
        }
        if (property_exists($json, 'odata.count')) {
            $this->totalCount = (int)$json->{'odata.count'};
        }
        $this->values = $json->value;
    }

    public function values()
    {
        return $this->values;
    }

    public function first()
    {
        return $this->count() ? $this->values[array_key_first($this->values)] : null;
    }

    public function last()
    {
        return $this->count() ? $this->values[array_key_last($this->values)] : null;
    }

    public function allCount()
    {
        return $this->totalCount;
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->values);
    }
}
