<?php

namespace MauticPlugin\ZenderWhatsappBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Entity\LeadNote;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST API controller for receiving inbound WhatsApp/SMS messages.
 *
 * Designed to be called by n8n (or any webhook forwarder) after Zender
 * forwards an incoming message.
 *
 * POST /api/whatsapp/inbound
 * {
 *   "phone": "+31612345678",
 *   "message": "Reply text",
 *   "channel": "whatsapp" | "sms",
 *   "timestamp": 1770836239
 * }
 */
class InboundApiController extends CommonApiController
{
    public function receiveAction(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'Invalid JSON body',
            ], 400);
        }

        $phone   = trim($data['phone'] ?? '');
        $message = trim($data['message'] ?? '');
        $channel = $data['channel'] ?? 'whatsapp';
        $timestamp = $data['timestamp'] ?? null;

        if (empty($phone)) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'Missing required field: phone',
            ], 400);
        }

        if (empty($message)) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'Missing required field: message',
            ], 400);
        }

        // Normalize phone: strip spaces/dashes, ensure + prefix
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
        if (!str_starts_with($phone, '+')) {
            $phone = str_starts_with($phone, '0')
                ? '+31'.substr($phone, 1)
                : '+'.$phone;
        }

        // Find contact by phone or mobile
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->getModel('lead');
        $em = $this->getDoctrine()->getManager();

        // Search by phone or mobile field
        $contact = $em->getRepository(\Mautic\LeadBundle\Entity\Lead::class)
            ->createQueryBuilder('l')
            ->where('l.phone = :phone OR l.mobile = :phone')
            ->setParameter('phone', $phone)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        // Also try without + prefix (some contacts store without it)
        if (!$contact) {
            $phoneWithout = ltrim($phone, '+');
            $contact = $em->getRepository(\Mautic\LeadBundle\Entity\Lead::class)
                ->createQueryBuilder('l')
                ->where('l.phone = :phone OR l.mobile = :phone')
                ->setParameter('phone', $phoneWithout)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        if (!$contact) {
            return new JsonResponse([
                'success'  => false,
                'error'    => 'Contact not found for phone: '.$phone,
                'phone'    => $phone,
                'matched'  => false,
            ], 404);
        }

        // Create a note on the contact with the inbound message
        $channelLabel = $channel === 'sms' ? 'SMS' : 'WhatsApp';
        $dateReceived = $timestamp
            ? (new \DateTime())->setTimestamp((int) $timestamp)
            : new \DateTime();

        $noteText = sprintf(
            "[Inbound %s] %s\n\nOntvangen: %s\nVan: %s",
            $channelLabel,
            $message,
            $dateReceived->format('d-m-Y H:i:s'),
            $phone
        );

        $note = new LeadNote();
        $note->setLead($contact);
        $note->setText($noteText);
        $note->setType('general');
        $note->setDateAdded($dateReceived);

        $em->persist($note);
        $em->flush();

        return new JsonResponse([
            'success'   => true,
            'contactId' => $contact->getId(),
            'name'      => $contact->getName(),
            'channel'   => $channel,
            'noteId'    => $note->getId(),
        ]);
    }
}
