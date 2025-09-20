# CityEvents

Display cultural events from **[iltaccodibacco.it](https://iltaccodibacco.it/)** on your WordPress site.  
Pick a city in the plugin settings and the widget/shortcode will show events in that city **plus everything within a 30 km radius**.

> **Why this plugin?**  
> iltaccodibacco.it is a trusted hub that covers events across Italy (concerts, theater, exhibitions, festivals, fairs, food & wine, and more).

---

## Demo
Visit [the-best.net/cultural-events-in-rome-italy/](https://the-best.net/cultural-events-in-rome-italy/)
## Features

- Direct integration with the iltaccodibacco.it events hub
- City selector in settings; auto-include events in a 30 km radius
- Output as a **Widget** _and_ as a **Shortcode**
- Responsive markup, theme-friendly
- Optional date/location display
- Basic caching to reduce external requests

---

## Requirements

- WordPress **5.8+**
- PHP **7.4+**

---

## Installation

### From WordPress Admin
1. Go to **Plugins → Add New → Upload Plugin** and upload the ZIP.
2. Activate **CityEvents Widget**.
3. Open **Settings → CityEvents** and select your **city**.

### Manual (FTP/SCP)
1. Copy the plugin folder to `wp-content/plugins/cityevents-widget`.
2. Activate the plugin in **Plugins**.
3. Open **Settings → CityEvents** and select your **city**.

---

## Usage

### Widget
- Go to **Appearance → Widgets** and add **CityEvents** to your sidebar or any widget area.

### Shortcode
Basic:
```text
[cityevents]
```

With options:
```text
[cityevents limit="10" show_date="1" show_location="1" date_format="F j, Y H:i" cache_minutes="30"]
```

---

## Attributes

| Attribute        | Type   | Default                                 | Description                                                     |
|------------------|--------|-----------------------------------------|-----------------------------------------------------------------|
| `limit`          | int    | `5`                                     | Max number of events to show.                                   |
| `show_date`      | bool   | `0` (false)                             | Show event date/time.                                           |
| `show_location`  | bool   | `0` (false)                             | Show event location.                                            |
| `date_format`    | string | WP `date_format` + `time_format`        | PHP date format used for output.                                |
| `cache_minutes`  | int    | `15`                                    | Cache TTL (minutes) for remote results.                         |
| `feed_url`       | string | _auto_ (by selected city)               | Override the default feed URL (advanced / debugging).           |

> The **city** is selected in the plugin settings. The **30 km radius** is applied automatically.

---


## Feed URL format (advanced)

The plugin automatically builds the feed URL based on the selected city. If you need to override it, the URL pattern is:

```
https://iltaccodibacco.it/{city-slug}/events.json
```

**City slug rules** (typical permalink rules):
1. Lowercase all letters.
2. Replace spaces and punctuation (including apostrophes) with hyphens.
3. Remove accents/diacritics (à → a, é → e, ò → o, ì → i, ù → u).
4. Collapse repeated hyphens and trim leading/trailing hyphens.

**Examples**

| City name            | City slug              | Feed URL                                                     |
|----------------------|------------------------|--------------------------------------------------------------|
| Roma                 | `roma`                 | `https://iltaccodibacco.it/roma/events.json`                 |
| Milano               | `milano`               | `https://iltaccodibacco.it/milano/events.json`               |
| Bari                 | `bari`                 | `https://iltaccodibacco.it/bari/events.json`                 |
| Lecce                | `lecce`                | `https://iltaccodibacco.it/lecce/events.json`                |
| Reggio nell’Emilia   | `reggio-nell-emilia`   | `https://iltaccodibacco.it/reggio-nell-emilia/events.json`   |
| Sant'Agata di Puglia | `sant-agata-di-puglia` | `https://iltaccodibacco.it/sant-agata-di-puglia/events.json` |

**Shortcode override** (optional):
```text
[cityevents feed_url="https://iltaccodibacco.it/roma/events.json"]
```



## Privacy & Data

This plugin fetches **public event data** from a third-party service (iltaccodibacco.it) to display it on your site.

### What the plugin does
- Performs **server-side HTTP requests** from your WordPress host to iltaccodibacco.it to retrieve event listings.
- **Caches** the raw response (transient/options) for the configured `cache_minutes` to reduce external calls.

### What the plugin does **not** do
- It does **not** collect, store, or transmit personal data of your site visitors.
- It does **not** set additional cookies.
- It does **not** profile users or track behavior.

### Data visible to the third party
- The remote service (iltaccodibacco.it) will see the **IP address of your server** (not your visitors’ IPs) when your site makes the HTTP request.
- Usual HTTP metadata (e.g., user agent of the request, request time, requested endpoint) may be logged by the remote service according to their policies.

### Your responsibilities
- Update your site’s **Privacy Policy** to disclose that your site fetches and displays events from iltaccodibacco.it and makes periodic server-side requests to that service.
- If you are a controller under GDPR/UK GDPR and include any additional integrations that process personal data, you must handle notices, consent, and DPAs as applicable (outside the scope of this plugin).

### Data retention
- Cached responses are stored as **WordPress transients/options** and expire automatically after `cache_minutes`.
- You can purge the cache by changing cache settings or clearing transients (e.g., via a performance plugin or WP-CLI).

---

## Localization

- Text domain: `cityevents-widget`
- Load path: `/languages`
- Generate/update the POT file:
```bash
wp i18n make-pot . languages/cityevents-widget.pot
```

---

## Development

- Code style: **WordPress Coding Standards (WPCS)**
- Escape on output (`esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`); sanitize on save (`sanitize_text_field()`, `esc_url_raw()`, `absint()`, etc.).
- Translation:
    - Use a **literal** text domain `'cityevents-widget'` in `__()`, `_e()`, etc.
    - Add `/* translators: ... */` comments for strings with placeholders.
- Widgets:
    - Print `$args['before_widget']`, `$args['before_title']`, `$args['after_title']`, `$args['after_widget']` **as provided by the theme** (with an inline PHPCS ignore comment if needed).

### PHPCS example
```bash
phpcs --standard=WordPress --extensions=php .
```

---

## Versioning & Releases

- Follows **SemVer**: `MAJOR.MINOR.PATCH` (e.g., `1.0.1` for bugfixes).
- For WordPress.org:
    - `readme.txt` → `Stable tag` must match the version in the main plugin file header.
    - Tag releases in SVN under `/tags/x.y.z`; keep active code in `/trunk`.

---

## Troubleshooting

- **No events showing**
    - Ensure a **city** is selected in **Settings → CityEvents**.
    - Temporarily increase `cache_minutes` or clear transients if rate-limited.
- **Dates/Times look wrong**
    - Adjust `date_format` or WordPress **Timezone** and **Formatting** settings.
- **Theme formatting issues**
    - The widget inherits your theme’s styles; add CSS overrides if needed.

---

## Support

- Use **GitHub Issues** for bug reports and feature requests.
- Please include WordPress/PHP versions, active theme, and plugin list for faster triage.

---

## License

This plugin is released under the **GPL-2.0-or-later** license.  
© The respective authors. “iltaccodibacco.it” is a third-party service and trademark of its owner.

---

## Credits

- Events data powered by  [iltaccodibacco.it](https://iltaccodibacco.it/).
- Built by [cognita srl](https://cognita.it/).
