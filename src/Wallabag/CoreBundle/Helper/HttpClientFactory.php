<?php

namespace Wallabag\CoreBundle\Helper;

use Graby\Ring\Client\SafeCurlHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Event\SubscriberInterface;

/**
 * Builds and configures the Guzzle HTTP client.
 */
class HttpClientFactory
{
    /** @var [\GuzzleHttp\Event\SubscriberInterface] */
    private $subscribers = [];

    /** @var \GuzzleHttp\Cookie\CookieJar */
    private $cookieJar;

    private $restrictedAccess;

    /**
     * HttpClientFactory constructor.
     *
     * @param \GuzzleHttp\Event\SubscriberInterface $authenticatorSubscriber Deprecated since 2.2. Use addSubscriber instead.
     * @param \GuzzleHttp\Cookie\CookieJar          $cookieJar
     * @param string                                $restrictedAccess        this param is a kind of boolean. Values: 0 or 1
     */
    public function __construct($authenticatorSubscriber, CookieJar $cookieJar, $restrictedAccess)
    {
        if ($authenticatorSubscriber !== null) {
            $this->subscribers[] = $authenticatorSubscriber;
        }
        $this->cookieJar = $cookieJar;
        $this->restrictedAccess = $restrictedAccess;
    }

    /**
     * @return \GuzzleHttp\Client|null
     */
    public function buildHttpClient()
    {
        if (0 === (int) $this->restrictedAccess) {
            return null;
        }

        // we clear the cookie to avoid websites who use cookies for analytics
        $this->cookieJar->clear();
        // need to set the (shared) cookie jar
        $client = new Client(['handler' => new SafeCurlHandler(), 'defaults' => ['cookies' => $this->cookieJar]]);
        if (!empty($this->subscribers)) {
            foreach ($this->subscribers as $subscriber) {
                $client->getEmitter()->attach($subscriber);
            }
        }

        return $client;
    }

    public function addSubscriber(SubscriberInterface $subscriber)
    {
        $this->subscribers[] = $subscriber;
    }
}
