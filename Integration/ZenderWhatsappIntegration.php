<?php

namespace MauticPlugin\ZenderWhatsappBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ZenderWhatsappIntegration extends AbstractIntegration
{
    protected bool $coreIntegration = false;

    public function getName(): string
    {
        return 'ZenderWhatsapp';
    }

    public function getDisplayName(): string
    {
        return 'Zender WhatsApp';
    }

    public function getSecretKeys(): array
    {
        return ['password'];
    }

    public function getRequiredKeyFields(): array
    {
        return [
            'password' => 'zender_wa.config.api_secret',
        ];
    }

    public function getAuthenticationType(): string
    {
        return 'none';
    }

    public function appendToForm(&$builder, $data, $formArea): void
    {
        if ('features' !== $formArea) {
            return;
        }

        $builder->add(
            'account',
            TextType::class,
            [
                'label'    => 'zender_wa.config.account',
                'required' => true,
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => 'WhatsApp account unique ID',
                ],
            ]
        );

        $builder->add(
            'priority',
            ChoiceType::class,
            [
                'choices' => [
                    'High (send immediately)' => '1',
                    'Normal (queued)'         => '2',
                ],
                'label'    => 'zender_wa.config.priority',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'api_url',
            TextType::class,
            [
                'label'    => 'zender_wa.config.api_url',
                'required' => false,
                'data'     => $data['api_url'] ?? 'https://zender.hollandworx.nl',
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => 'https://zender.hollandworx.nl',
                ],
            ]
        );
    }
}
