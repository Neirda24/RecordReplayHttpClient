<?php

namespace Symfony\HttpClientRecorderBundle\HttpClient;

use Symfony\Component\HttpClient\AsyncDecoratorTrait;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\HttpClientRecorderBundle\Enum\RecorderMode;
use Symfony\HttpClientRecorderBundle\Har\HarFile;
use Symfony\HttpClientRecorderBundle\Matcher\DefaultMatcher;
use Symfony\HttpClientRecorderBundle\Matcher\MatcherInterface;
use Symfony\HttpClientRecorderBundle\Store\StoreInterface;

final class RecorderHttpClient implements HttpClientInterface
{
    use AsyncDecoratorTrait;

    private static RecorderMode $mode = RecorderMode::PassThrough;
    private static string $record = 'default.har';

    public function __construct(
        private readonly HttpClientInterface $inner,
        private readonly StoreInterface $store,
        private readonly MatcherInterface $matcher = new DefaultMatcher(),
    ) {
        $this->client = $inner;
    }

    public static function setMode(RecorderMode $mode): void
    {
        self::$mode = $mode;
    }

    public static function setRecord(string $record): void
    {
        self::$record = $record;
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if (RecorderMode::PassThrough === self::$mode) {
            return $this->inner->request($method, $url, $options);
        }

        $har = $this->store->load(self::$record);

        if (RecorderMode::Playback === self::$mode) {
            return $this->playback($har, $method, $url, $options);
        }

        if (RecorderMode::Record === self::$mode) {
            return $this->record($har, $method, $url, $options);
        }

        if (RecorderMode::NewEpisodes === self::$mode) {
            try {
                return $this->playback($har, $method, $url, $options);
            } catch (TransportException) {
                return $this->record($har, $method, $url, $options);
            }
        }

        throw new \RuntimeException('Unknown recorder mode.');
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function playback(HarFile $har, string $method, string $url, array $options): ResponseInterface
    {
        $response = $har->findEntry($this->matcher, $method, $url, $options);

        return (new MockHttpClient($response))->request($method, $url, $options);
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function record(HarFile $har, string $method, string $url, array $options): ResponseInterface
    {
        $response = $this->inner->request($method, $url, $options);

        $har->addEntry($this->matcher, $response, $method, $url, $options);

        $this->store->save(self::$record, $har);

        return $response;
    }
}
