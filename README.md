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

Same as the Fictioneer theme, the plugin is not available in the official WordPress library and needs to be installed and updated manually.

1. Download the zip file from the [latest release](https://github.com/Tetrakern/fictioneer-email-notifications/releases).
2. In your WordPress dashboard, go to **Plugins > Add New Plugin**.
3. Click on **Upload Plugin** at the top.
4. Select the downloaded zip file and click **Install Now**.
5. Activate the plugin once the installation is complete.

After installing the plugin, follow these steps to set up MailerSend:

1. Register with [MailerSend](https://www.mailersend.com/help/getting-started).
2. [Add your domain for API access](https://www.mailersend.com/help/how-to-verify-and-authenticate-a-sending-domain). This step may seem intimidating if you are not experienced with such things, but MailerSend provides a comprehensive guide with examples for different hosts. If you are on a managed host, you should be able to ask the support for help as well.
3. Once your domain is set up, add your API key in your WordPress dashboard under **Notifications > Settings**.

MailerSend also offers a [WordPress plugin](https://www.mailersend.com/integrations/official-smtp-plugin) for your day-to-day transactional emails, such as email confirmations. Please note that it is not required for this notification plugin and does not assist with its functionality. But if you already have a MailerSend account, you may as well use it.

## Frontend

The plugin integrates seamlessly into the Fictioneer theme. You’ll find a new option in the \[Subscribe] popup menu, the user menu, and the mobile menu to open the subscription modal. The user profile is extended with a new section to link your subscription, which can use a different email address, and enable Follows to automatically subscribe to a story.

Additionally, you can use the `[fictioneer_email_subscription]` shortcode to render an email input field that serves as a toggle for the modal. The optional `placeholder=""` parameter allows you to customize the text. Beyond this, there is nothing else to do on the frontend.

![User Menu Entry](repo/assets/frontend_2.png?raw=true)
![Subscribe Button](repo/assets/frontend_1.png?raw=true)
![Profile Section](repo/assets/frontend_3.png?raw=true)
![Shortcode](repo/assets/shortcode.png?raw=true)

## Admin

The plugin menus are added low in the admin sidebar under **Notifications** with a letter icon.

### Notifications

This page provides a list table of all notifications, both unsent and sent (if not cleared). Blog posts, stories, and chapters are automatically enqueued when published for the first time. You also have the option to add them manually using their ID, which can be found in the URL of the post edit screen. Duplicates and ineligible posts are either skipped or marked accordingly if already present in the table. The status column indicates the current state of each notification, while other columns provide additional information.

Each eligible post features a meta box in the sidebar with related information, such as the dates when it has been enqueued and sent. You can send notifications multiple times if desired. Two convenient checkbox flags allow you to re-enqueue a post once when updated or exclude it entirely. This is currently limited to administrators.

<p align="center">
  <img src="repo/assets/list_table.png?raw=true" height="185" alt="List Table" />
  <img src="repo/assets/meta_box.png?raw=true" height="185" alt="Meta Box" />
</p>

### Send Emails

This is where you can send notifications to subscribers. The plugin does not send notifications automatically, as it prioritizes control over convenience. Sending notifications requires just the push of a button, which is considered manageable.

The queue is processed via Fetch API requests, providing you with real-time information about the status. Since the plugin pushes emails in bulk to the MailerSend service, you can use the ID link to monitor the progress on their end.

Note that the MailerSend API has a rate limit of 10 requests per minute and 1,000 requests per day, at least for the free tier. The queue ensures that you do not exceed this rate limit. If something goes wrong, you can retry processing the queue — successful batches are not sent again. Incomplete queues are stored for 24 hours.

![Start New Queue](repo/assets/queue_1.png?raw=true)
![Processed Queue](repo/assets/queue_2.png?raw=true)

### Subscribers

### Templates

### Settings

### Log

## Frequently Asked Question
