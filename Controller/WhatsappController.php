<?php

namespace MauticPlugin\ZenderWhatsappBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use MauticPlugin\ZenderWhatsappBundle\Form\Type\SendWhatsappType;
use MauticPlugin\ZenderWhatsappBundle\Transport\ZenderWhatsappTransport;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WhatsappController extends FormController
{
    public function sendWhatsappAction(Request $request, ZenderWhatsappTransport $transport, $objectId = ''): JsonResponse|Response
    {
        if ('POST' === $request->getMethod()) {
            $data     = $request->request->all()['zender_send_whatsapp'] ?? [];
            $objectId = $data['contactId'] ?? $objectId;
        }

        $leadModel = $this->getModel('lead');
        $lead      = $leadModel->getEntity($objectId);

        if (!$lead) {
            $this->addFlashMessage('mautic.lead.lead.error.notfound', [], 'error');

            return new JsonResponse(['closeModal' => true, 'flashes' => $this->getFlashContent()]);
        }

        if (!$this->security->hasEntityAccess(
            'lead:leads:editown',
            'lead:leads:editother',
            $lead->getPermissionUser()
        )) {
            $this->addFlashMessage('mautic.core.error.accessdenied', [], 'error');

            return new JsonResponse(['closeModal' => true, 'flashes' => $this->getFlashContent()]);
        }

        if ('GET' === $request->getMethod()) {
            $route = $this->generateUrl(
                'mautic_plugin_zenderwhatsapp_action',
                ['objectAction' => 'sendWhatsapp']
            );

            return $this->delegateView([
                'viewParameters' => [
                    'form' => $this->createForm(
                        SendWhatsappType::class,
                        ['contactId' => (string) $objectId],
                        ['action' => $route]
                    )->createView(),
                    'contact' => $lead,
                ],
                'contentTemplate' => '@ZenderWhatsapp/SendWhatsapp/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_contact_index',
                    'mauticContent' => 'lead',
                    'route'         => $route,
                ],
            ]);
        }

        if ('POST' === $request->getMethod()) {
            $messageType   = $data['messageType'] ?? 'text';
            $message       = trim($data['message'] ?? '');
            $mediaUrl      = trim($data['mediaUrl'] ?? '');
            $mediaType     = $data['mediaType'] ?? 'image';
            $documentUrl   = trim($data['documentUrl'] ?? '');
            $documentName  = trim($data['documentName'] ?? '');
            $documentType  = $data['documentType'] ?? 'pdf';

            if (empty($message)) {
                $this->addFlashMessage('zender_wa.send.error.no_message', [], 'error');

                return new JsonResponse(['closeModal' => true, 'flashes' => $this->getFlashContent()]);
            }

            if ('media' === $messageType && empty($mediaUrl)) {
                $this->addFlashMessage('zender_wa.send.error.no_media', [], 'error');

                return new JsonResponse(['closeModal' => true, 'flashes' => $this->getFlashContent()]);
            }

            if ('document' === $messageType && empty($documentUrl)) {
                $this->addFlashMessage('zender_wa.send.error.no_document', [], 'error');

                return new JsonResponse(['closeModal' => true, 'flashes' => $this->getFlashContent()]);
            }

            // Replace tokens in message
            $message = str_replace(
                ['{contactfield=firstname}', '{contactfield=lastname}', '{contactfield=email}', '{contactfield=phone}', '{contactfield=mobile}'],
                [$lead->getFirstname(), $lead->getLastname(), $lead->getEmail(), $lead->getPhone(), $lead->getMobile()],
                $message
            );

            // Get recipient phone number
            $recipient = $lead->getLeadPhoneNumber();
            if (empty($recipient)) {
                $this->addFlashMessage('zender_wa.send.error.no_phone', [], 'error');

                return new JsonResponse(['closeModal' => true, 'flashes' => $this->getFlashContent()]);
            }

            // Normalize phone number
            $recipient = preg_replace('/[\s\-\(\)]/', '', $recipient);
            if (!str_starts_with($recipient, '+')) {
                $recipient = str_starts_with($recipient, '0')
                    ? '+31'.substr($recipient, 1)
                    : '+'.$recipient;
            }

            $result = $transport->sendWhatsapp(
                $recipient,
                $messageType,
                $message,
                'media' === $messageType ? $mediaUrl : null,
                'media' === $messageType ? $mediaType : null,
                'document' === $messageType ? $documentUrl : null,
                'document' === $messageType ? $documentName : null,
                'document' === $messageType ? $documentType : null,
            );

            if (true === $result) {
                $this->addFlashMessage('zender_wa.send.success');
            } else {
                $this->addFlashMessage('zender_wa.send.error.failed_detail', ['%error%' => $result], 'error');
            }

            return new JsonResponse(['closeModal' => true, 'flashes' => $this->getFlashContent()]);
        }

        return new Response('Bad Request', 400);
    }
}
