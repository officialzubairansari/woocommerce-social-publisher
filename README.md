# WooCommerce Social Publisher

[![Version](https://img.shields.io/badge/version-1.0.1-blue.svg)](https://github.com/officialzubairansari/woocommerce-social-publisher)
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

---

## 🚀 Quick Start

1. **Install Plugin**: Upload `woocommerce-social-publisher.zip` via **Plugins &rarr; Add New &rarr; Upload Plugin** and activate it.
2. **Configure Meta API**:
   - Go to **WooCommerce &rarr; Social Publisher &rarr; Settings &rarr; Facebook**.
   - Enter your **Meta App ID** & **App Secret**, then click **Connect with Meta**.
   - Select your target **Facebook Page** (linked Instagram account is auto-detected).
3. **Publish Products**:
   - Open **WooCommerce &rarr; Products**.
   - Check the products you want to share.
   - Choose **Publish to Social Media** from the **Bulk Actions** dropdown and click **Apply**.
   - Preview captions and images, then click **Publish Now** or **Schedule**.

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

Run the included automated test suite:

```bash
php tests/run-tests.php
```

---

## 📄 License

This project is licensed under the [GNU General Public License v2.0](https://www.gnu.org/licenses/gpl-2.0.html).

Developed by **[Zubair Ansari](https://github.com/officialzubairansari/woocommerce-social-publisher)**.
