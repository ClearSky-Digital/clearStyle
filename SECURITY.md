# Security

## Reporting a problem

Please report a security problem privately, and please do not post details in a public issue.

Open the [Security tab](https://github.com/gcollins808/csd-site-styles/security/advisories/new) of this repository and choose **Report a vulnerability**. This creates a private security advisory that only the maintainer can see, through GitHub's private vulnerability reporting.

Please include the plugin version, the WordPress and PHP versions, and steps to reproduce.

This is a small project and support is best effort, with no promised response times. We will read a report, look into it, and tell you what we find. A fix is released as a new version on the Releases page.

## What counts

The plugin's main safeguard is the CSS check in `csd-site-styles/includes/css-sanitizer.php`: it refuses CSS that could end the style element, carry markup, load something from another address or run a script. Ways around that check are in scope, as are problems in the settings page (permissions, nonces, escaping, file upload handling) and the gallery frame.

Supported versions: the latest release.
