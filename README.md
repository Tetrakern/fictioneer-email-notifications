# Fictioneer Email Notifications

<p>
  <a href="https://github.com/Tetrakern/fictioneer-email-notifications"><img alt="Plugin: 1.0" src="https://img.shields.io/badge/plugin-1.0-blue?style=flat" /></a>
  <a href="LICENSE.md"><img alt="License: GPL v3" src="https://img.shields.io/badge/license-GPL%20v3-blue?style=flat" /></a>
  <a href="https://wordpress.org/download/"><img alt="WordPress 6.1+" src="https://img.shields.io/badge/WordPress-%3E%3D6.1-blue?style=flat" /></a>
  <a href="https://www.php.net/"><img alt="PHP: 7.4+" src="https://img.shields.io/badge/php-%3E%3D7.4-blue?logoColor=white&style=flat" /></a>
</p>

This WordPress plugin is built exclusively for the Fictioneer theme, version 5.19.0 or higher. It enables guests and users to subscribe to selected content updates via email. You can choose to receive notifications for all new content, specific post types, or individual stories and taxonomies. The plugin is compatible with all cache plugins.

Currently, the plugin is integrated with the [MailerSend](https://www.mailersend.com/) email service provider. MailerSend offers a generous free plan of 3,000 emails per month and bulk emails. This should last you a long time before you need a paid plan, at which point you should have the support to afford it.

<p align="center">
  <img src="repo/assets/fcnen_modal_preview.gif?raw=true" alt="Modal Preview" />
</p>

## Installation

Same as the Fictioneer theme, the plugin is not available from the official library and needs to be installed and updated manually.

1. Download the zip file from the [latest release](https://github.com/Tetrakern/fictioneer-email-notifications/releases).
2. In your WordPress dashboard, go to **Plugins > Add New Plugin**.
3. Click on **Upload Plugin** at the top.
4. Select the downloaded zip file and click **Install Now**.
5. Activate the plugin once the installation is complete.

After installing the plugin, follow these steps to set up MailerSend:

1. Register with [MailerSend](https://www.mailersend.com/help/getting-started).
2. [Add your domain for API access](https://www.mailersend.com/help/how-to-verify-and-authenticate-a-sending-domain). This step may seem intimidating if you are not experienced with such things, but MailerSend provides a comprehensive guide with examples for different hosts. If you are on a managed host, you should be able to ask the support for help.
3. Once your domain is set up, add your API key in your WordPress dashboard under **Notifications > Settings**.

MailerSend also offers a [WordPress plugin](https://www.mailersend.com/integrations/official-smtp-plugin) for your day-to-day transactional emails, such as email change confirmations. Please note that it is not required for this notification plugin and does not assist with its functionality. But if you already have a MailerSend account, you may as well use it.

## Configuration






## Limitations
