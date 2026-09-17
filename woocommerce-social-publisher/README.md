# WooCommerce Social Publisher

**WooCommerce Social Publisher** is an enterprise-grade WordPress and WooCommerce plugin that integrates directly into the native WooCommerce Products catalog (`/wp-admin/edit.php?post_type=product`). It empowers store managers to select products using native checkboxes and publish them directly to a **Facebook Page** and **Instagram Professional (Business or Creator)** account using Meta's official Graph API.

Developed by **[Zubair Ansari](https://github.com/officialzubairansari/woocommerce-social-publisher)** | **[GitHub Repository](https://github.com/officialzubairansari/woocommerce-social-publisher)**

---

## Key Features

- **Native Bulk Action Integration**: Adds a clean `Publish to Social Media` bulk action to `/wp-admin/edit.php?post_type=product`.
- **Review & Preview Before Publishing**: Selecting products and clicking "Apply" routes the administrator to a secure Preview & Configuration screen without publishing immediately.
- **Per-Product Customization**: Edit captions, select or deselect product gallery images (all images preselected by default), or toggle target platforms on a per-product basis before sending.
- **Dynamic Post Template Engine**: Replaceable tokens like `{product_title}`, `{regular_price}`, `{sale_price}`, `{currency}`, `{sku}`, `{product_url}`, `{category}`, `{tags}`, `{custom_text}`, and `{hashtags}`.
- **Conditional Formatting & Clean-up**: If a product has no sale price, lines containing `{sale_price}` are cleanly omitted. Consecutive blank lines are automatically collapsed.
- **Duplicate Publishing Prevention**: Detects previously published products per platform and alerts the admin, requiring an explicit "Publish Again" toggle to avoid spamming.
- **Official Meta Graph API (v21.0+)**:
  - **Facebook Pages**: Native photo uploads (`POST /{page-id}/photos`), feed postings with links, and multi-photo carousel support.
  - **Instagram Professional**: Strict compliance with Meta's official 2-step Container Publishing API (`POST /{ig-user-id}/media` -> Status Polling -> `POST /{ig-user-id}/media_publish`).
- **Asynchronous Background Queue & Live Progress**:
  - **Live Progress Runner**: Interactively process 50–100 products with real-time progress bars, per-product status updates, and inline retries without browser or PHP timeouts.
  - **WooCommerce Action Scheduler**: Native background queue processing for asynchronous batches and scheduled posts.
- **Publishing Scheduling**: Schedule posts for a future date and time respecting the store's configured timezone.
- **Comprehensive Publishing History**: Filterable audit log showing product info, target platform, published dates, external Meta post IDs, retry counters, and detailed API diagnostics.
- **Enterprise Security**: Strict WordPress capability checks (`manage_woocommerce`), CSRF nonces, AES-256 token encryption, and credentials masking.

---

## Installation

1. Download `woocommerce-social-publisher.zip`.
2. In your WordPress admin, navigate to **Plugins &rarr; Add New &rarr; Upload Plugin**.
3. Choose the ZIP file and click **Install Now**.
4. Click **Activate Plugin**.
5. Ensure **WooCommerce** is active.

---

## Meta Developer App Setup (Step-by-Step)

To publish via Meta's official Graph API, you must configure a Meta Developer App:

### Step 1: Create a Meta App
1. Go to [developers.facebook.com](https://developers.facebook.com/) and log in with your Meta credentials.
2. Click **My Apps &rarr; Create App**.
3. Select **Other** as the use case &rarr; Click **Next**.
4. Select **Business** as the app type &rarr; Click **Next**.
5. Give your app a name (e.g. `My Store Social Publisher`) and enter your contact email.
6. Click **Create App**.

### Step 2: Add Facebook Login for Business
1. On the App Dashboard, find **Facebook Login for Business** and click **Set Up**.
2. Under **Facebook Login &rarr; Settings**, locate **Valid OAuth Redirect URIs**.
3. Copy the Redirect URI from **WooCommerce &rarr; Social Publisher &rarr; Settings &rarr; Facebook** in your WordPress admin:
   ```
   https://yourstore.com/wp-admin/admin.php?page=woocommerce-social-publisher&tab=facebook&wsp_action=oauth_callback
   ```
4. Paste it into **Valid OAuth Redirect URIs** and click **Save Changes**.

### Step 3: Required Permissions & Scopes
In order to publish content, your Meta App requires the following permissions:
- `pages_manage_posts`: Required to create posts on Facebook Pages.
- `pages_read_engagement`: Required to verify page engagement and status.
- `pages_show_list`: Required to list your managed Facebook Pages.
- `instagram_content_publish`: Required to publish posts and carousels to Instagram.
- `instagram_basic`: Required for basic Instagram profile access.

*Note: For testing with accounts that have admin/developer roles on your Meta App, no App Review is needed. For general third-party access, submit your app for Meta App Review.*

### Step 4: Connect in WordPress
1. In Meta App Dashboard, navigate to **App Settings &rarr; Basic**.
2. Copy your **App ID** and **App Secret**.
3. In WordPress, navigate to **WooCommerce &rarr; Social Publisher &rarr; Settings &rarr; Facebook**.
4. Enter your **Meta App ID** and **Meta App Secret**.
5. Click **Save Changes**.
6. Click **Connect with Meta (Facebook & Instagram)**.
7. Approve permissions in the Meta dialog.
8. Select your active **Facebook Page**. Your connected **Instagram Professional** account is automatically detected.

---

## Instagram Professional Setup Requirements

Meta's Content Publishing API enforces strict prerequisites:
1. **Professional Account**: Your Instagram account must be converted to an **Instagram Business** or **Instagram Creator** account. Personal accounts cannot publish via API.
2. **Linked to Facebook Page**: The Instagram account must be connected to your Facebook Page in **Meta Business Suite &rarr; Settings &rarr; Linked Accounts**.
3. **Public Image URLs**: Meta's servers download media directly from your store. Image URLs must be publicly accessible (not on `localhost` or private intranet) with an aspect ratio between `4:5` (portrait) and `1.91:1` (landscape) in JPEG or PNG format.

---

## How to Select Products & Publish

1. Navigate to **WooCommerce &rarr; Products** (`/wp-admin/edit.php?post_type=product`).
2. Select the products you wish to publish using the native checkboxes.
3. In the **Bulk Actions** dropdown, select **Publish to Social Media**.
4. Click **Apply**.
5. The **Social Publisher Preview** screen opens:
   - **Review Media**: All product images (featured image + gallery images) are selected by default as a carousel post. Click any thumbnail or checkbox to select or deselect specific images, or click "Deselect All" / "Select All".
   - **Review Caption**: The caption is pre-generated using your store template. Click into the textarea to make custom edits for that specific product.
   - **Platform Checkboxes**: Customize whether a product goes to Facebook, Instagram, or both.
   - **Duplicate Warning**: If a product has previously been published, a yellow warning banner appears. Check **Publish Again** if you intentionally want another post.
6. Choose:
   - **Publish Now**: Starts the real-time batch runner with a progress bar (`12 / 50 completed`), showing live checkmarks or error messages per product.
   - **Schedule**: Click `Schedule...`, pick a future date and time, and click `Confirm & Schedule All`.

---

## Post Template System & Placeholders

Configure your global template under **WooCommerce &rarr; Social Publisher &rarr; Settings &rarr; Post Template**.

### Available Placeholders

| Token | Description |
|---|---|
| `{product_title}` | WooCommerce Product Title |
| `{description}` | Full Product Description (HTML stripped) |
| `{short_description}` | Short Product Description |
| `{regular_price}` | Formatted store regular price |
| `{sale_price}` | Formatted sale price (omitted if not on sale) |
| `{currency}` | Currency code (e.g. `PKR`) or symbol |
| `{sku}` | Product SKU |
| `{product_url}` | Public customer-facing product permalink |
| `{category}` | Assigned product categories |
| `{tags}` | Assigned product tags |
| `{custom_text}` | Global custom text snippet from Settings |
| `{hashtags}` | Global hashtags from Settings |

### Smart Formatting Rules
- **Automatic Sale Price Cleanup**: If a product is not on sale or regular price equals sale price, any line containing `{sale_price}` is omitted automatically.
- **Disabled Field Resolution**: If a field is unchecked in **Post Content**, its placeholder resolves to empty.
- **Blank Line Collapsing**: Multiple consecutive blank lines are collapsed into a single clean paragraph separator.

---

## Duplicate Prevention

To prevent accidental reposting of the same product:
- The plugin logs every published product and platform in `wp_wsp_social_posts`.
- On the preview screen, if a product was previously published, a warning displays:
  `Warning: This product was previously published to Facebook on Sep 17, 2026.`
- The administrator must explicitly check `Publish Again` to proceed.
- Duplicate behavior can be configured in **Settings &rarr; General** (`Show warning`, `Strictly block`, or `Allow`).

---

## Background Queue & Scheduling

- **Action Scheduler**: Built-in support for WooCommerce's Action Scheduler ensures that batches of 50–100 products never hit PHP execution timeouts or memory limits.
- **Timezone Awareness**: Scheduling adheres to your WordPress store timezone configured in **Settings &rarr; General**.
- **Retry Mechanism**: If a temporary API or network timeout occurs, administrators can click `[Retry]` in the Publishing History table or directly on the preview card.

---

## Publishing History & Audit Logs

Navigate to **WooCommerce &rarr; Social History** (`admin.php?page=wsp-history`):
- Filter by platform (`Facebook`, `Instagram`) and status (`Published`, `Failed`, `Scheduled`, `Processing`).
- View thumbnail, product link, caption snippet, external Meta ID, attempt counts, and dates.
- Click the **Eye icon** (`View Details`) to inspect the complete log record and raw API diagnostic messages.
- Bulk delete or retry failed records in one click.

---

## Troubleshooting & API Limitations

| Issue | Cause & Solution |
|---|---|
| `[Meta Error #190]` | Access token has expired or password changed. Reconnect your account in **Settings &rarr; Facebook**. |
| `[Meta Error #200]` | Missing permissions. Ensure your Meta App has requested `pages_manage_posts` and `instagram_content_publish`. |
| `[Meta Error #100] Aspect ratio` | Instagram requires image aspect ratio between 4:5 and 1.91:1. Select an alternate gallery image or resize the product photo. |
| `Meta cannot download image` | The website is running on `localhost` or a private domain. Meta servers cannot crawl local URLs. Use an ngrok or Cloudflare tunnel. |
| `No Instagram account linked` | Connect your Instagram Professional account to your Facebook Page in Meta Business Suite under **Linked Accounts**. |

---

## Automated Tests

Run the complete CLI test suite:
```bash
php tests/run-tests.php
```

Assertions verify:
- Template engine & token replacement
- Conditional sale price removal
- Caption builder with WC product data
- Security, encryption, and secret masking
- Duplicate post detection and override
- Meta Graph API mocked Facebook & Instagram publishing
- Action Scheduler queue enqueuing and scaling (1, 10, 50, 100 products)

---

## License

Distributed under the GNU General Public License v2 or later.
