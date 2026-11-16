# Elementor Form Counter Pro

A WordPress plugin that counts unique email submissions from Elementor Pro forms and displays submission totals through shortcodes.

## Features

- Tracks submissions through Elementor Pro's `elementor_pro/forms/new_record` hook.
- Maintains a per-form unique-email list and count.
- Provides shortcode counters and progress-style displays.
- Includes an administrator settings page and dashboard widget.
- Supports CSV export, manual resets, and optional milestone notifications.

## Requirements

- WordPress 6.0+
- PHP 7.4+
- Elementor Pro (the form submission hook is provided by Elementor Pro)

## Installation

1. Download the plugin ZIP from the repository.
2. Upload and activate the plugin in WordPress.
3. Confirm Elementor Pro is installed and active.
4. Submit a test form containing an email field.
5. Add a counter shortcode to a page.

## Shortcodes

Examples shown by the plugin settings include:

```text
[form_counter form="Waitlist"]
[form_counter form="Waitlist" type="progress" target="100"]
[form_counter form="Waitlist" style="bold" font_size="24px" font_color="#ff0000"]
[form_counter form="Waitlist" style="animated"]
```

The form name must match the Elementor form name used for tracking.

## Data and privacy

The plugin stores normalized email addresses to identify repeat submissions. These are personal data. Site owners should disclose this processing in their privacy notice, restrict access to WordPress administrators, define a retention period, and remove stored data when it is no longer needed. A count-only mode or privacy-preserving keyed hash would reduce the amount of personal data retained.

Milestone webhooks are configured by an administrator. Only configure destinations you trust.

## Development

The plugin source is in `elementor-form-counter-pro/`. The ZIP files in the repository are packaged release artifacts; rebuild them after source changes so the downloadable package matches the tracked source.

## Compatibility

Test with the current WordPress and Elementor Pro versions, especially after major plugin updates. Test duplicate submissions, multiple forms with similar names, concurrent submissions, export, reset, and uninstall behaviour.

## Maintainer

[Precious Okechukwu Nwosu](https://github.com/Novavamp)
