# WooCommerce Social Publisher

[![Version](https://img.shields.io/badge/version-1.0.3-blue.svg)](https://github.com/officialzubairansari/woocommerce-social-publisher)
[![WordPress](https://img.shields.io/badge/WordPress-%3E%3D%205.8-21759b.svg)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-%3E%3D%205.0-96588a.svg)](https://woocommerce.com)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D%207.4-777bb4.svg)](https://php.net)
[![Meta Graph API](https://img.shields.io/badge/Meta%20Graph%20API-v21.0+-0866ff.svg)](https://developers.facebook.com)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

> **Publish WooCommerce products directly to Facebook Pages and Instagram Professional accounts using Meta's official Graph API.**

**WooCommerce Social Publisher** integrates seamlessly into the WordPress admin, enabling store managers to select products in bulk, preview and customize captions and gallery media, and publish or schedule social posts without third-party SaaS subscriptions.

---

## ✨ Features

- **Native Bulk Action** – Select products directly from **WooCommerce &rarr; Products** and launch social publishing with one click.
- **Interactive Preview & Customizer** – Review pre-generated captions, toggle platforms, and select/deselect gallery images per product before posting.
- **Carousel & Multi-Image Posts** – Full support for single photo uploads and multi-image carousels on both Facebook and Instagram.
- **Smart Template Engine** – Customize post formatting with dynamic tokens (`{product_title}`, `{regular_price}`, `{sale_price}`, `{product_url}`, `{sku}`, `{hashtags}`). Unused sale price lines automatically collapse.
- **Duplicate Prevention** – Tracks publishing history and alerts admins if a product was previously published to avoid spamming.
- **Background Queue & Scheduling** – Handle batches (50–100+ items) effortlessly with real-time UI progress bars and native WooCommerce Action Scheduler background processing.
- **Audit History & Logs** – Detailed publishing history with direct links to live Meta posts, status filters, and one-click retries.
- **Enterprise Security** – Built for security with AES-256 token encryption, nonce verification, and HPOS compatibility.

---

## 📋 Requirements

| Requirement | Minimum Version / Detail |
|---|---|
| **WordPress** | 5.8+ |
| **WooCommerce** | 5.0+ (HPOS Compatible) |
| **PHP** | 7.4+ (`curl`, `openssl` enabled) |
| **Meta App** | Meta Developer App with Facebook Login for Business |
| **Accounts** | Facebook Page & Linked Instagram Professional (Business/Creator) |
| **Account Access** | Facebook profile with access to the intended Page and its business portfolio |

---

## 🚀 Quick Start

1. **Install Plugin**: Upload `woocommerce-social-publisher.zip` via **Plugins &rarr; Add New &rarr; Upload Plugin** and activate it.
2. **Configure Meta API**:
   - Go to **WooCommerce &rarr; Social Publisher &rarr; Settings &rarr; Facebook**.
   - Follow [Facebook Login for Business setup](#facebook-login-for-business-setup) below to configure the domain, callback URL, permissions, and Configuration ID.
   - Save your settings, connect with Meta, and select your target Facebook Page. Its linked Instagram professional account is discovered automatically.
3. **Publish Products**:
   - Open **WooCommerce &rarr; Products**.
   - Check the products you want to share.
   - Choose **Publish to Social Media** from the **Bulk Actions** dropdown and click **Apply**.
   - Preview captions and images, then click **Publish Now** or **Schedule**.

---

## Facebook Login for Business Setup

This is the connection setup confirmed working with version **1.0.3**. For Instagram publishing, link the Instagram professional account to the Facebook Page. During login, explicitly grant this Meta app access to the intended assets.

1. **Register the domain and callback.** Open the plugin's Facebook settings and copy the displayed **Meta App Domain** into **Meta App Dashboard &rarr; App settings &rarr; Basic &rarr; App Domains**. Enter only the hostname, without a protocol or path. Copy the complete **Valid OAuth Redirect URI** into **Facebook Login for Business &rarr; Settings &rarr; Valid OAuth Redirect URIs**. Use the exact URL displayed by your installation, including its query parameters.
2. **Create a login configuration.** In the same Meta app, open **Facebook Login for Business &rarr; Configurations**. Create or edit a configuration using **User Access Token**, enable Page assets, and include all permissions listed below. Address any app role, permission access, or review requirements displayed by Meta for the account you will use.
3. **Save the credentials in WordPress.** Enter the **Meta App ID**, **App Secret**, and **Facebook Login for Business Configuration ID** in the plugin's Facebook settings. These must belong to the same Meta app. Click **Save Changes** before connecting.
4. **Authorize the assets.** Click **Connect with Meta**, sign in with the Facebook profile that has access to the Page and business portfolio, and approve the requested access. Select the intended business portfolio, Facebook Page, and linked Instagram professional account when prompted.
5. **Confirm the connection.** Check the active Facebook Page and linked Instagram details in the plugin. If more than one Page is available, select the intended Page and save. Use **Test Connection** to check the active connection before publishing.

### Permissions

| Permission | Purpose in this integration |
|---|---|
| `business_management` | Access business assets involved in discovering Pages belonging to a business portfolio. |
| `pages_show_list` | Discover the Facebook Pages authorized for this app. |
| `pages_read_engagement` | Read Page information used by the integration. |
| `pages_manage_posts` | Publish product content to the selected Facebook Page. |
| `instagram_basic` | Discover and read the linked Instagram professional account. |
| `instagram_content_publish` | Publish product media to the linked Instagram account. |

When a Configuration ID is saved, the plugin sends `config_id` during authorization; configure its permissions and assets in Meta. Without a Configuration ID, the plugin sends the permission list using `scope` and requests declined permissions again. Use the Configuration ID for the Business Login setup described above.

### Meta API Usage

The plugin continues to use Facebook Login for Business. It exchanges the authorization code through `/oauth/access_token`, retrieves authorized Pages and their Page tokens through `/me/accounts`, and reads each Page's `instagram_business_account` to discover its linked Instagram account. When Page discovery returns an empty list, it calls `/me/permissions` to identify missing permission grants.

Publishing uses the selected Page and its linked Instagram account. The plugin does not request email, friends, gender, or birthday permissions, or separately fetch personal profile fields.

### Connection Troubleshooting

| Symptom | What to check |
|---|---|
| **Meta error #191 during token exchange** | Confirm App Domains and the exact callback URL match the plugin settings, and that the App ID matches the Meta app you configured. Install version 1.0.2 or later, which fixes callback URL encoding during token exchange, then start a fresh connection attempt. |
| **Authentication succeeds but no Pages are found** | Use version 1.0.3 or later and read the permission diagnostic. Enable `pages_show_list` and `business_management` in the app and login configuration. Reconnect and approve the intended Page and business portfolio. If both permissions are granted, check asset selection, profile access, and the app access requirements shown by Meta. |
| **The Page is connected but Instagram is missing** | Confirm the intended Instagram professional account is linked to that Page and was shared with the app during authorization. Re-authorize to refresh the discovered accounts. |

If an existing authorization omits the intended assets, open **Facebook Business Integrations**, edit this app's access, and allow the intended Page and business portfolio before reconnecting. Updating the plugin or configuration alone does not add permissions to an existing token.

---

## 🏷️ Template Placeholders

| Token | Description |
|---|---|
| `{product_title}` | Product name |
| `{regular_price}` | Regular price with currency symbol |
| `{sale_price}` | Sale price (automatically omitted if not on sale) |
| `{product_url}` | Public product permalink |
| `{sku}` | Product SKU |
| `{category}` | Comma-separated product categories |
| `{tags}` | Comma-separated product tags |
| `{hashtags}` | Configured hashtags from Settings |
| `{custom_text}` | Custom call-to-action or promo text |

---

## 🧪 Testing

To check a connection manually, complete the setup above, confirm the selected Page and Instagram account, and run **Test Connection**. Publish a test product to the intended test accounts, then check both the publishing history and the resulting posts. A successful login confirms authentication; it does not by itself confirm publishing permissions.

For Meta reviewers, provide a working WordPress test-site URL and administrator credentials through Meta's designated reviewer access fields. The review flow is: sign in, open the plugin's Facebook settings, connect with Meta and authorize test assets, select a test product through **Products &rarr; Bulk Actions &rarr; Publish to Social Media**, preview, and publish. Confirm the results in publishing history and on the selected test accounts.

Run the included automated test suite:

```bash
php tests/run-tests.php
```

The suite uses mocked WordPress and Meta responses. It covers OAuth callback encoding, token storage, Business Login configuration, empty Page permission diagnostics, publishing, and queue behavior. It does not verify a live Meta account or its permissions.

---

## Changelog

### 1.0.3

- Added `business_management` to the requested permission list.
- Added Facebook Login for Business Configuration ID support.
- Added permission checks and actionable guidance when Meta returns no accessible Pages.

### 1.0.2

- Fixed GET request encoding so the complete callback URL, authorization code, and tokens reach Meta unchanged.
- Fixed token decryption when the binary initialization vector contains delimiter characters.

---

## 📄 License

This project is licensed under the [GNU General Public License v2.0](https://www.gnu.org/licenses/gpl-2.0.html).

Developed by **[Zubair Ansari](https://github.com/officialzubairansari/woocommerce-social-publisher)**.
