<?php

return [
    'name'        => 'Zender WhatsApp',
    'description' => 'WhatsApp transport for Zender API (zender.hollandworx.nl)',
    'version'     => '1.0.0',
    'author'      => 'Radata',

    'routes' => [
        'main' => [
            'mautic_plugin_zenderwhatsapp_action' => [
                'path'       => '/zenderwhatsapp/{objectAction}/{objectId}',
                'controller' => 'MauticPlugin\ZenderWhatsappBundle\Controller\WhatsappController::executeAction',
            ],
        ],
    ],

    'services' => [
        'integrations' => [
            'mautic.integration.zenderwhatsapp' => [
                'class'     => \MauticPlugin\ZenderWhatsappBundle\Integration\ZenderWhatsappIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'request_stack',
                    'router',
                    'translator',
                    'monolog.logger.mautic',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                    'mautic.lead.field.fields_with_unique_identifier',
                ],
            ],
        ],
        'events' => [
            'mautic.zenderwhatsapp.subscriber.buttons' => [
                'class'     => \MauticPlugin\ZenderWhatsappBundle\EventListener\ButtonSubscriber::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'translator',
                    'router',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.zender_send_whatsapp' => [
                'class' => \MauticPlugin\ZenderWhatsappBundle\Form\Type\SendWhatsappType::class,
            ],
        ],
        'others' => [
            'mautic.sms.transport.zenderwhatsapp.configuration' => [
                'class'     => \MauticPlugin\ZenderWhatsappBundle\Transport\Configuration::class,
                'arguments' => [
                    'mautic.helper.integration',
                ],
            ],
            'mautic.sms.transport.zenderwhatsapp' => [
                'class'     => \MauticPlugin\ZenderWhatsappBundle\Transport\ZenderWhatsappTransport::class,
                'arguments' => [
                    'mautic.sms.transport.zenderwhatsapp.configuration',
                    'monolog.logger.mautic',
                ],
                'tag'          => 'mautic.sms_transport',
                'tagArguments' => [
                    'channel'          => 'ZenderWhatsapp',
                    'integrationAlias' => 'ZenderWhatsapp',
                ],
            ],
        ],
    ],
];
