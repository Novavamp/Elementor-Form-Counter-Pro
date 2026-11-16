<div align="center">

# Elementor Form Counter Pro

**Live form-submission counts and progress indicators for Elementor forms.**

![WordPress](https://img.shields.io/badge/WordPress-plugin-21759B?logo=wordpress&logoColor=white)
![Elementor](https://img.shields.io/badge/Elementor-integration-92003B?logo=elementor&logoColor=white)
![Version](https://img.shields.io/badge/version-1.3.0-334155)

</div>

## Overview

Elementor Form Counter Pro adds reusable counters for Elementor form activity. It provides shortcode-based displays backed by WordPress options and AJAX requests, with administrative settings for default presentation.

## Features

- Count unique email submissions per Elementor form (handled by the plugin counter component).
- Display a live count through the `[form_counter]` shortcode.
- Display a progress indicator when a target is supplied.
- Configure display templates and typography defaults in the plugin settings.
- Override the display style, font size, colour, and family per shortcode.
- Retrieve count updates through WordPress AJAX endpoints.

## Requirements

- WordPress 6.0 or later.
- PHP 7.4 or later.
- Elementor installed and active for Elementor form integration.

## Installation

1. Download the plugin ZIP from this repository.
2. In WordPress, open **Plugins → Add New Plugin → Upload Plugin**.
3. Upload `elementor-form-counter-pro.zip` and activate it.
4. Configure the plugin defaults in the available settings screen.

You can also install the extracted plugin directory into `wp-content/plugins/` and activate it from the WordPress dashboard.

## Shortcode

Basic counter:

```text
[form_counter form="Waitlist"]
```

Progress display with a target:

```text
[form_counter form="Waitlist" type="progress" target="500"]
```

Presentation overrides:

```text
[form_counter form="Waitlist" style="minimal" font_size="24px" font_color="#222222" font_family="Arial"]
```

The `form` value should match the Elementor form name. Available template names depend on the installed plugin version and settings.

## How it works

The shortcode resolves a form name to a stable slug, reads the corresponding stored count, and renders a counter element. The front end can request updated values through WordPress AJAX. Requests are protected with a WordPress nonce and the form slug is sanitised before use.

## Development

The plugin source is in `elementor-form-counter-pro/`. The main plugin file loads the counter and admin components and registers the front-end assets and shortcode.

## Compatibility note

This plugin extends Elementor form workflows. Test it on a staging site with your specific Elementor version and form configuration before using it on a production site.

---

Maintained by [Precious Okechukwu Nwosu](https://github.com/Novavamp).