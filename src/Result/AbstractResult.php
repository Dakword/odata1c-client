<?php
declare(strict_types=1);

namespace Dakword\OData1C\Client\Result;

use GuzzleHttp\Psr7\Request as HttpRequest;
use GuzzleHttp\Psr7\Response as HttpResponse;

abstract class AbstractResult
{
	protected bool $ok = true;
	protected array $odataError = [
		'code' => null,
		'message' => null,
	];
	protected $odataMetadata = null;
	protected HttpRequest $request;
	protected HttpResponse $response;

    public function __construct(HttpRequest $request, HttpResponse $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

	public function isOK(): bool
	{
		return $this->ok === true;
	}

    public function getOdataMetadata(): mixed
    {
        return $this->odataMetadata;
    }

    public function getOdataErrorCode(): int|null
    {
        return $this->odataError['code'];
    }

    public function getOdataErrorMessage(): int|null
    {
        return $this->odataError['message'];
    }

    public function getResponseCode(): int
    {
        return $this->response->getStatusCode();
    }

	public function getRequest(): HttpRequest
	{
		return $this->request;
	}

	public function getResponse(): HttpResponse
	{
		return $this->response;
	}

    protected function getJsonContent()
    {
        $this->response->getBody()->rewind();
        $content = $this->response->getBody()->getContents();
        return json_decode($content);
    }
}