=== cityevents ===
Contributors: cognitasrl
Tags: events, culture, italy, agenda, concerts
Requires at least: 5.8
Tested up to: 6.8
Stable tag: 0.1.10
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display cultural events from iltaccodibacco.it on your WordPress site, filtered by city and including events within a 30 km radius.

== Description ==

CityEvents Widget allows you to easily integrate cultural events from [iltaccodibacco.it](https://www.iltaccodibacco.it) into your WordPress site.
iltaccodibacco.it is a trusted hub that covers events all over Italy, including concerts, theater shows, exhibitions, festivals, fairs, and more.

You can select a city, and the plugin will automatically display all events within a 30 km radius, giving your visitors a comprehensive and always up-to-date overview of what's happening in the area.

**Main Features**
- Direct integration with the iltaccodibacco.it event hub.
- Select your preferred city.
- Automatically includes events within a 30 km radius.
- Display events via **widget** or **shortcode**.
- Responsive layout for desktop, tablet, and mobile.
- Content updated in real time.

== Installation ==

1. Upload the plugin folder `cityevents-widget` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the “Plugins” menu in WordPress.
3. Go to **Settings → CityEvents** and choose your reference city.
4. Add the **CityEvents Widget** to your sidebar or use the shortcode:

== External Service & Remote Requests ==

This plugin connects to ONE external service only:

- Service name: iltaccodibacco.it (events hub for Italy)
- Domain: https://iltaccodibacco.it
- Purpose: fetch public cultural events to display on your site
- Endpoint pattern (GET, HTTPS):
  https://iltaccodibacco.it/{city-slug}/events.json
  (e.g., https://iltaccodibacco.it/roma/events.json)

When requests happen:
- On frontend when the widget/shortcode renders (and optionally in admin previews).
- Results are cached to reduce calls.

What is sent:
- Standard HTTP request from your server to iltaccodibacco.it over HTTPS.
- No personal data from your visitors is sent by this plugin.
- As with any outgoing request, the remote server sees your server’s IP and basic HTTP metadata.

What is stored:
- The remote response is cached in WordPress (transients/options) for the configured TTL (`cache_minutes`, default 15 minutes). No personal data is stored.

How to disable:
- Remove the widget and/or the shortcode, or deactivate the plugin.

Notes:
- If the remote service is unavailable or returns an error, the plugin fails gracefully and shows a generic message.