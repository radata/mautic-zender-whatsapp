<?php

namespace MauticPlugin\ZenderWhatsappBundle\Transport;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Sms\TransportInterface;
use Psr\Log\LoggerInterface;

class ZenderWhatsappTransport implements TransportInterface
{
    public function __construct(
        private Configuration $configuration,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Send a WhatsApp message via the Zender API.
     *
     * @param string $content
     *
     * @return bool|string true on success, error message on failure
     */
    public function sendSms(Lead $lead, $content)
    {
        $number = $lead->getLeadPhoneNumber();
        if (empty($number)) {
            return 'mautic.sms.transport.error.no_phone';
        }

        $number = $this->normalizePhoneNumber($number);

        try {
            $apiUrl    = $this->configuration->getApiUrl();
            $apiSecret = $this->configuration->getApiSecret();
            $account   = $this->configuration->getAccount();
            $priority  = $this->configuration->getPriority();
        } catch (ConfigurationException $e) {
            $this->logger->warning('Zender WhatsApp not configured: '.$e->getMessage());

            return $e->getMessage();
        }

        return $this->send($apiUrl, $apiSecret, $account, $number, 'text', $content, $priority);
    }

    /**
     * Send a WhatsApp message with optional media/document attachments.
     *
     * @return bool|string true on success, error message on failure
     */
    public function sendWhatsapp(
        string $recipient,
        string $type,
        string $message,
        ?string $mediaUrl = null,
        ?string $mediaType = null,
        ?string $documentUrl = null,
        ?string $documentName = null,
        ?string $documentType = null,
    ): bool|string {
        try {
            $apiUrl    = $this->configuration->getApiUrl();
            $apiSecret = $this->configuration->getApiSecret();
            $account   = $this->configuration->getAccount();
            $priority  = $this->configuration->getPriority();
        } catch (ConfigurationException $e) {
            $this->logger->warning('Zender WhatsApp not configured: '.$e->getMessage());

            return $e->getMessage();
        }

        return $this->send(
            $apiUrl, $apiSecret, $account, $recipient, $type, $message, $priority,
            $mediaUrl, $mediaType, $documentUrl, $documentName, $documentType
        );
    }

    private function send(
        string $apiUrl,
        string $apiSecret,
        string $account,
        string $recipient,
        string $type,
        string $message,
        string $priority,
        ?string $mediaUrl = null,
        ?string $mediaType = null,
        ?string $documentUrl = null,
        ?string $documentName = null,
        ?string $documentType = null,
    ): bool|string {
        $postFields = [
            'secret'    => $apiSecret,
            'account'   => $account,
            'recipient' => $recipient,
            'type'      => $type,
            'message'   => $message,
            'priority'  => $priority,
        ];

        if ('media' === $type && !empty($mediaUrl)) {
            $postFields['media_url']  = $mediaUrl;
            $postFields['media_type'] = $mediaType ?: 'image';
        }

        if ('document' === $type && !empty($documentUrl)) {
            $postFields['document_url']  = $documentUrl;
            $postFields['document_name'] = $documentName ?: 'document.pdf';
            $postFields['document_type'] = $documentType ?: 'pdf';
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl.'/api/send/whatsapp',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if (!empty($error)) {
            $this->logger->error('Zender WhatsApp cURL error: '.$error);

            return 'Zender API error: '.$error;
        }

        $data = json_decode($response, true);

        if (null === $data) {
            $this->logger->error('Zender WhatsApp invalid response: '.$response);

            return 'Zender: invalid API response';
        }

        if (200 !== ($data['status'] ?? 0)) {
            $errorMsg = $data['message'] ?? 'Unknown error';
            $this->logger->warning('Zender WhatsApp send failed: '.$errorMsg, [
                'recipient' => $recipient,
                'status'    => $data['status'] ?? 'unknown',
                'response'  => $response,
            ]);

            return 'Zender: '.$errorMsg;
        }

        $this->logger->info('Zender WhatsApp sent successfully', [
            'recipient' => $recipient,
            'type'      => $type,
            'messageId' => $data['data']['messageId'] ?? null,
        ]);

        return true;
    }

    private function normalizePhoneNumber(string $number): string
    {
        $number = preg_replace('/[\s\-\(\)]/', '', $number);

        if (str_starts_with($number, '+')) {
            return $number;
        }

        if (str_starts_with($number, '0')) {
            return '+31'.substr($number, 1);
        }

        return '+'.$number;
    }
}
