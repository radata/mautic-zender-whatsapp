<?php

namespace MauticPlugin\ZenderWhatsappBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\SmsBundle\Entity\Stat;
use MauticPlugin\ZenderWhatsappBundle\Transport\ZenderWhatsappTransport;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * REST API controller for sending WhatsApp messages via Zender.
 *
 * Endpoint: GET /api/whatsapp/{smsId}/contact/{contactId}/send
 *
 * Uses an SMS template for message content and sends via the
 * Zender WhatsApp transport, bypassing the active SMS transport.
 */
class WhatsappApiController extends CommonApiController
{
    /**
     * Send a WhatsApp message to a contact using an SMS template.
     *
     * @param int $smsId     Mautic SMS template ID (for message content)
     * @param int $contactId Mautic contact ID
     */
    public function sendToContactAction(
        ZenderWhatsappTransport $transport,
        int $smsId,
        int $contactId
    ): JsonResponse {
        // Load SMS template
        /** @var \Mautic\SmsBundle\Model\SmsModel $smsModel */
        $smsModel = $this->getModel('sms');
        $sms      = $smsModel->getEntity($smsId);

        if (!$sms || !$sms->isPublished()) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'SMS template not found or not published',
            ], 404);
        }

        // Load contact
        $leadModel = $this->getModel('lead');
        $lead      = $leadModel->getEntity($contactId);

        if (!$lead) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'Contact not found',
            ], 404);
        }

        // Get phone number
        $phone = $lead->getLeadPhoneNumber();
        if (empty($phone)) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'Contact has no phone number',
            ], 400);
        }

        // Normalize phone number to E.164
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
        if (!str_starts_with($phone, '+')) {
            $phone = str_starts_with($phone, '0')
                ? '+31'.substr($phone, 1)
                : '+'.$phone;
        }

        // Get message content and replace contact field tokens
        $message = $sms->getMessage();
        $message = str_replace(
            [
                '{contactfield=firstname}',
                '{contactfield=lastname}',
                '{contactfield=email}',
                '{contactfield=phone}',
                '{contactfield=mobile}',
            ],
            [
                $lead->getFirstname(),
                $lead->getLastname(),
                $lead->getEmail(),
                $lead->getPhone(),
                $lead->getMobile(),
            ],
            $message
        );

        // Send via Zender WhatsApp transport
        $result = $transport->sendWhatsapp($phone, 'text', $message);

        // Record stat entry for contact activity timeline
        $stat = new Stat();
        $stat->setDateSent(new \DateTime());
        $stat->setLead($lead);
        $stat->setSms($sms);
        $stat->setTrackingHash(str_replace('.', '', uniqid('', true)));
        $stat->setSource('api');

        $details = [
            'message' => $message,
            'type'    => 'text',
            'channel' => 'whatsapp',
        ];

        if (true === $result) {
            $stat->setDetails($details);
            $smsModel->getStatRepository()->saveEntity($stat);

            return new JsonResponse([
                'success' => true,
                'status'  => 'Delivered',
                'result'  => [
                    'sent'    => true,
                    'channel' => 'whatsapp',
                    'id'      => $sms->getId(),
                    'name'    => $sms->getName(),
                    'content' => $message,
                ],
                'errors' => [],
            ]);
        }

        $details['error'] = $result;
        $stat->setIsFailed(true);
        $stat->setDetails($details);
        $smsModel->getStatRepository()->saveEntity($stat);

        return new JsonResponse([
            'success' => false,
            'error'   => is_string($result) ? $result : 'Unknown error',
        ], 500);
    }
}
