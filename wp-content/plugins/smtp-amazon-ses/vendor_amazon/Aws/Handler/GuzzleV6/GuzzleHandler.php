<?php

namespace YaySMTPAmazonSES\Aws3\Aws\Handler\GuzzleV6;

use Exception;
use YaySMTPAmazonSES\Aws3\GuzzleHttp\Client;
use YaySMTPAmazonSES\Aws3\GuzzleHttp\ClientInterface;
use YaySMTPAmazonSES\Aws3\GuzzleHttp\Exception\ConnectException;
use YaySMTPAmazonSES\Aws3\GuzzleHttp\Exception\RequestException;
use YaySMTPAmazonSES\Aws3\GuzzleHttp\Promise;
use YaySMTPAmazonSES\Aws3\GuzzleHttp\TransferStats;
use YaySMTPAmazonSES\Aws3\Psr\Http\Message\RequestInterface as Psr7Request;

/**
 * A request handler that sends PSR-7-compatible requests with Guzzle 6.
 */
class GuzzleHandler {
  /** @var ClientInterface */
  private $client;
  /**
   * @param ClientInterface $client
   */
  public function __construct(\YaySMTPAmazonSES\Aws3\GuzzleHttp\ClientInterface $client = null) {
    $this->client = $client ?: new \YaySMTPAmazonSES\Aws3\GuzzleHttp\Client();
  }
  /**
   * @param Psr7Request $request
   * @param array       $options
   *
   * @return Promise\Promise
   */
  public function __invoke(\YaySMTPAmazonSES\Aws3\Psr\Http\Message\RequestInterface $request, array $options = []) {
    $request = $request->withHeader('User-Agent', $request->getHeaderLine('User-Agent') . ' ' . \YaySMTPAmazonSES\Aws3\GuzzleHttp\default_user_agent());
    return $this->client->sendAsync($request, $this->parseOptions($options))->otherwise(static function (\Exception $e) {
      $error = ['exception' => $e, 'connection_error' => $e instanceof ConnectException, 'response' => null];
      if ($e instanceof RequestException && $e->getResponse()) {
        $error['response'] = $e->getResponse();
      }
      return new \YaySMTPAmazonSES\Aws3\GuzzleHttp\Promise\RejectedPromise($error);
    });
  }
  private function parseOptions(array $options) {
    if (isset($options['http_stats_receiver'])) {
      $fn = $options['http_stats_receiver'];
      unset($options['http_stats_receiver']);
      $prev = isset($options['on_stats']) ? $options['on_stats'] : null;
      $options['on_stats'] = static function (\YaySMTPAmazonSES\Aws3\GuzzleHttp\TransferStats $stats) use ($fn, $prev) {
        if (is_callable($prev)) {
          $prev($stats);
        }
        $transferStats = ['total_time' => $stats->getTransferTime()];
        $transferStats += $stats->getHandlerStats();
        $fn($transferStats);
      };
    }
    return $options;
  }
}
