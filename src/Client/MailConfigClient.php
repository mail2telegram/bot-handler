<?php

namespace M2T\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class MailConfigClient implements MailConfigClientInterface
{
    protected const URL = 'https://autoconfig.thunderbird.net/v1.1/';

    protected LoggerInterface $logger;
    protected Client $client;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->client = new Client(
            [
                'base_uri' => static::URL,
                'timeout' => 5.0,
            ]
        );
    }

    public function get(string $domain): array
    {
        try {
            $response = $this->client->request('GET', $domain);
        } catch (ClientException $e) {
            if ($e->getCode() === 404) {
                $this->logger->debug('MailConfigClientResponse: 404');
                return [];
            }
            $this->logger->error((string) $e);
            return [];
            /** @phan-suppress-next-line PhanRedefinedClassReference */
        } catch (GuzzleException $e) {
            $this->logger->error((string) $e);
            return [];
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($response->getBody(), 'SimpleXMLElement');
        if (false === $xml) {
            $errors = libxml_get_errors();
            foreach ($errors as $e) {
                $this->logger->error((string) $e);
            }
            return [];
        }

        $data = [];
        $imap = $xml->xpath('emailProvider/incomingServer[@type="imap"]');
        if ($imap) {
            $data = [
                'imapHost' => (string) $imap[0]->hostname,
                'imapPort' => (string) $imap[0]->port,
                'imapSocketType' => strtolower((string) $imap[0]->socketType),
            ];
        }
        $smtp = $xml->xpath('emailProvider/outgoingServer[@type="smtp"]');
        if ($smtp) {
            $data += [
                'smtpHost' => (string) $smtp[0]->hostname,
                'smtpPort' => (string) $smtp[0]->port,
                'smtpSocketType' => strtolower((string) $smtp[0]->socketType),
            ];
        }

        $this->logger->debug('MailConfigClientResponse: ', $data);
        return $data;
    }
}
