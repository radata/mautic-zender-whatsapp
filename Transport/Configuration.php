<?php

namespace MauticPlugin\ZenderWhatsappBundle\Transport;

use Mautic\PluginBundle\Helper\IntegrationHelper;

class Configuration
{
    private string $apiSecret = '';
    private string $apiUrl    = 'https://zender.hollandworx.nl';
    private string $account   = '';
    private string $priority  = '2';
    private bool $configured  = false;

    public function __construct(
        private IntegrationHelper $integrationHelper,
    ) {
    }

    public function getApiSecret(): string
    {
        $this->setConfiguration();

        return $this->apiSecret;
    }

    public function getApiUrl(): string
    {
        $this->setConfiguration();

        return rtrim($this->apiUrl, '/');
    }

    public function getAccount(): string
    {
        $this->setConfiguration();

        return $this->account;
    }

    public function getPriority(): string
    {
        $this->setConfiguration();

        return $this->priority;
    }

    /**
     * @throws ConfigurationException
     */
    private function setConfiguration(): void
    {
        if ($this->configured) {
            return;
        }

        $integration = $this->integrationHelper->getIntegrationObject('ZenderWhatsapp');
        if (!$integration || !$integration->getIntegrationSettings()->getIsPublished()) {
            throw new ConfigurationException('Zender WhatsApp integration is not enabled');
        }

        $keys = $integration->getDecryptedApiKeys();
        if (empty($keys['password'])) {
            throw new ConfigurationException('Zender API secret is not configured');
        }

        $this->apiSecret = $keys['password'];

        $features        = $integration->getIntegrationSettings()->getFeatureSettings();
        $this->apiUrl    = !empty($features['api_url']) ? $features['api_url'] : 'https://zender.hollandworx.nl';
        $this->account   = $features['account'] ?? '';
        $this->priority  = $features['priority'] ?? '2';

        if (empty($this->account)) {
            throw new ConfigurationException('WhatsApp account ID is not configured');
        }

        $this->configured = true;
    }
}
