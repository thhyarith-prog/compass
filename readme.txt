=== Compass ===
Tags: seo, schema, sitemap, redirects, open graph
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Search visibility made clear: titles and descriptions with a live Google preview, SEO checks, social cards, business schema, sitemap, redirects and a 404 monitor.

== Description ==

Compass is the SEO plugin by Prasat Digital. It does what search engines actually reward, explains every setting in plain words, and stays out of your way.

**On every page**
* SEO title and meta description from templates you set once, overridable per post, page, category or tag.
* Canonical URLs, robots rules (noindex for search results, date archives and anything you choose) and large image previews in Google.
* Open Graph and X cards, so links look right on Facebook, Telegram, Messenger, LinkedIn and X.
* Structured data as one connected graph: website with search box, your organization, local business (café, restaurant, shop, hotel… with address, phone, opening hours and map location) or person, each page, articles and breadcrumbs.

**In the editor**
* A Compass panel under every post with a live Google preview, length meters and a social card preview.
* SEO checks for your focus keyphrase: title, description, URL, first paragraph, subheadings, density, image alt text and links. Khmer text is measured correctly (it has no spaces between words).
* An SEO column in the posts list.

**Site-wide**
* XML sitemap built on WordPress's own: hidden pages are left out, last-modified dates added, /sitemap.xml works.
* Redirects (301, 302, 307, 410 Gone) with wildcards and hit counts, and a 404 monitor that shows the page linking to each broken address, with one-click fixes.
* IndexNow for instant re-crawling by Bing, Yandex and others (off by default).
* Verification codes for Google, Bing, Yandex, Baidu and Pinterest; extra robots.txt rules; breadcrumbs shortcode.
* A dashboard that flags the mistakes that hide sites from Google, including "Discourage search engines" left on after launch, with a one-click fix.

**Moving from another plugin**
Import titles, descriptions, keyphrases, canonical, noindex and social settings from Yoast SEO or Rank Math under Tools. While another SEO plugin is active, Compass doesn't print tags on your pages, so nothing is duplicated.

Pairs with Slipstream (speed): changing an SEO setting or adding a redirect clears Slipstream's page cache automatically.

== External services ==

When IndexNow is switched on (Sitemap & robots), the addresses of published or updated pages are sent to https://api.indexnow.org, which shares them with participating search engines. It is off by default. IndexNow terms: https://www.indexnow.org/terms

== Frequently Asked Questions ==

= Will Compass conflict with Yoast or Rank Math? =
No. While one of them is active Compass stays quiet on the front end. Import their data, then deactivate them.

= Where is the sitemap? =
At /wp-sitemap.xml (also reachable as /sitemap.xml). Submit it in Google Search Console.

== Credits ==

* Interface fonts: Inter (The Inter Project Authors) and Noto Sans Khmer (Google), SIL Open Font License 1.1, bundled in assets/fonts.

== Changelog ==

= 1.0.6 =
* New: updates now come from the plugin's GitHub releases (github.com/thhyarith-prog), with the normal one-click "Update now" and automatic updates.

= 1.0.5 =
* Fix: declares "Update URI", so WordPress never offers an unrelated WordPress.org plugin with the same folder name as an update for this one.

= 1.0.4 =
* New: reports to the one-click "Check my website" on the Prasat Digital page.

= 1.0.3 =
* New: a new app icon for Compass and for the Prasat Digital menu, plus a short motto and credit (Made by Prasat Digital, founded by Thyyarith) at the bottom of every Prasat Digital screen.

= 1.0.2 =
* Fix: on a static front page the social card (og:title) shows the site title, not the page's internal name ("Homepage").

= 1.0.1 =
* Fix: a title no longer repeats the same words, such as a front page named after the site ("Acme – Acme").
* Fix: page-builder template types (Flatsome UX Blocks, Elementor templates, Divi layouts and similar) are hidden from search and the sitemap by default.

= 1.0.0 =
* First release.
