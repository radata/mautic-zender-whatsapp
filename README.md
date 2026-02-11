# Mautic Zender WhatsApp Plugin

Mautic WhatsApp transport plugin for sending WhatsApp messages via the [Zender](https://zender.hollandworx.nl) API. Supports text, media (images/audio/video), and document attachments.

## Features

- SMS transport compatible (text-only) for campaigns and broadcast messages
- Send WhatsApp messages through Zender API
- "Send WhatsApp" button on contact pages with modal dialog
- Support for text, media, and document message types
- Media attachments: images, audio, video via URL
- Document attachments: PDF, XML, XLS, XLSX, DOC, DOCX via URL
- Basic token replacement (`{contactfield=firstname}`, etc.)
- Configurable send priority (immediate or queued)
- E.164 phone number normalization
- Full logging of send results

## Requirements

- Mautic 7.x (Docker FPM image)
- PHP 8.0+
- A Zender account with API access and a linked WhatsApp account

## Installation

### Via Composer (Docker)

Ensure the composer and npm directories exist with correct permissions:

```bash
docker exec --user root mautic_web mkdir -p /var/www/.composer/cache
docker exec --user root mautic_web chown -R www-data:www-data /var/www/.composer
docker exec --user root mautic_web mkdir -p /var/www/.npm
docker exec --user root mautic_web chown -R www-data:www-data /var/www/.npm
```

Allow dev packages (only needed once per Mautic installation):

```bash
docker exec --user www-data --workdir /var/www/html mautic_web \
  composer config minimum-stability dev
docker exec --user www-data --workdir /var/www/html mautic_web \
  composer config prefer-stable true
```

Add the GitHub repository and install the plugin:

```bash
docker exec --user www-data --workdir /var/www/html mautic_web \
  composer config repositories.mautic-zender-whatsapp vcs \
  https://github.com/radata/mautic-zender-whatsapp --no-interaction
docker exec --user www-data --workdir /var/www/html mautic_web \
  composer require radata/mautic-zender-whatsapp:dev-main \
  -W --no-interaction --ignore-platform-req=ext-gd
```

> The `--ignore-platform-req=ext-gd` flag is needed because the `mautic/mautic:7.0-fpm` Docker image has a broken GD CLI extension (`libavif.so.15` missing). GD works fine at runtime via PHP-FPM.

Update to the latest version:

```bash
docker exec --user www-data --workdir /var/www/html mautic_web \
  composer update radata/mautic-zender-whatsapp \
  -W --no-interaction --ignore-platform-req=ext-gd
```

If the npm post-install hook fails after composer require, fix it:

```bash
docker exec --user root mautic_web rm -rf /var/www/html/node_modules
docker exec --user root mautic_web mkdir -p /var/www/.npm
docker exec --user root mautic_web chown -R www-data:www-data /var/www/.npm
docker exec --user www-data --workdir /var/www/html mautic_web npm ci --no-audit
```

### Post-Installation

Clear cache (hard delete required), reload plugins, then enable in UI:

```bash
docker exec --user www-data mautic_web rm -rf /var/www/html/var/cache/prod
docker exec --user www-data --workdir /var/www/html mautic_web php bin/console cache:warmup --env=prod
docker exec --user www-data --workdir /var/www/html mautic_web php bin/console mautic:plugins:reload
```

1. Go to **Settings > Plugins > Zender WhatsApp**
2. Set **Published** to **Yes**
3. Enter your API Secret from Zender (Tools > API Keys)
4. In Features tab: enter your WhatsApp Account ID (unique ID from Zender dashboard or `/get/wa.accounts`)
5. Set Priority and API URL if needed
6. Go to **Settings > Configuration > SMS Settings** and select **Zender WhatsApp** as transport

## Configuration

In the plugin settings:

| Field | Description |
|---|---|
| **API Secret** | Your Zender API secret key |
| **WhatsApp Account ID** | Unique ID of your linked WhatsApp account (from Zender dashboard) |
| **Priority** | High (send immediately) or Normal (queued) |
| **API URL** | Zender API endpoint (default: `https://zender.hollandworx.nl`) |

## Usage

### Campaign / Broadcast Messages

When selected as the SMS transport, campaigns and broadcasts send text-only WhatsApp messages via `sendSms()`.

### Send WhatsApp from Contact Page

When the plugin is enabled, a **Send WhatsApp** button appears on each contact's detail page. Clicking it opens a modal that supports:

- **Text messages** - plain text with contact field tokens
- **Media messages** - text + image/audio/video via URL
- **Document messages** - text + PDF/XML/XLS/XLSX/DOC/DOCX via URL

Contact field tokens like `{contactfield=firstname}` are replaced automatically.

## Plugin Structure

```
plugins/ZenderWhatsappBundle/
├── Config/config.php                        # Service & route registration
├── Controller/
│   └── WhatsappController.php               # "Send WhatsApp" modal controller
├── EventListener/
│   └── ButtonSubscriber.php                 # Injects "Send WhatsApp" button on contact page
├── Form/Type/
│   └── SendWhatsappType.php                 # Modal form with message type fields
├── Integration/
│   └── ZenderWhatsappIntegration.php        # Settings UI (API key, account ID)
├── Resources/views/SendWhatsapp/
│   └── form.html.twig                       # Modal template
├── Transport/
│   ├── Configuration.php                    # Reads credentials from integration settings
│   ├── ConfigurationException.php
│   └── ZenderWhatsappTransport.php          # Sends via POST /api/send/whatsapp
├── Translations/en_US/messages.ini
├── ZenderWhatsappBundle.php                 # Bundle class
└── composer.json
```

## Uninstall

```bash
docker exec --user www-data --workdir /var/www/html mautic_web \
  composer remove radata/mautic-zender-whatsapp -W --no-interaction
docker exec --user www-data --workdir /var/www/html mautic_web \
  composer config --unset repositories.mautic-zender-whatsapp
docker exec --user www-data mautic_web rm -rf /var/www/html/var/cache/prod
docker exec --user www-data --workdir /var/www/html mautic_web php bin/console cache:warmup --env=prod
docker exec --user www-data --workdir /var/www/html mautic_web php bin/console mautic:plugins:reload
```

## License

MIT - see [LICENSE](LICENSE) for details.
