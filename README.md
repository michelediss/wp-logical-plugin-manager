# The Logical Plugin Manager

**Logical Plugin Manager** is a WordPress plugin that provides centralized management for your WordPress plugins hosted on GitHub and the WordPress repository. This plugin allows you to easily install, update, and activate plugins directly from your WordPress dashboard.

## Features

- **GitHub Integration**: Manage plugins hosted on GitHub, including the ability to pull the latest version.
- **WordPress Repository Integration**: Install and activate plugins directly from the WordPress repository.
- **Bulk Actions**: Perform bulk installations and updates for multiple plugins simultaneously.
- **Automated Updates**: Schedule daily automatic updates for your GitHub-hosted plugins.

## Installation

1. Download the `logical-plugin-manager.zip` file from the [releases page](https://yourwebsite.com/plugin-manager).
2. Upload the `logical-plugin-manager.zip` file via the WordPress Admin Dashboard:
   - Go to `Plugins` > `Add New`.
   - Click `Upload Plugin`.
   - Choose the `logical-plugin-manager.zip` file and click `Install Now`.
   - Once installed, click `Activate`.
3. Alternatively, you can unzip the file and upload the `logical-plugin-manager` folder to the `/wp-content/plugins/` directory via FTP.

## Usage

1. Once the plugin is activated, go to `Settings` > `Logical Plugin Manager` in your WordPress Admin Dashboard.
2. You'll see two sections:
   - **Logical Stuff Plugins**: Manage your GitHub-hosted plugins.
   - **WordPress Repository Plugins**: Manage plugins from the WordPress repository.
3. Select the plugins you want to manage and apply the desired actions (e.g., Pull from GitHub, Install from WordPress Repository, Activate).

### GitHub Plugins

- You can pull the latest version of a GitHub-hosted plugin by clicking the "Pull" button next to the plugin's name.

### WordPress Repository Plugins

- To install a new plugin from the WordPress repository, click the "Install" button.
- To activate an installed plugin, click the "Activate" button.

### Bulk Actions

- You can select multiple plugins and use the bulk action dropdown to install or update them in one go.

### Automatic Updates

- The plugin automatically schedules a daily update at 03:33 AM for all GitHub-hosted plugins.

## Screenshots

![Plugin Manager Overview](screenshots/plugin-manager-overview.png)
*Screenshot showing the main interface of the Logical Plugin Manager.*

## Changelog

### Version 1.0
- Initial release with GitHub and WordPress repository integration.
- Added bulk actions and automatic update scheduling.

## Frequently Asked Questions (FAQ)

### How does the automatic update work?
The plugin schedules a daily task to check for updates on all GitHub-hosted plugins and installs them if available.

### What happens if there is an error during plugin installation?
If an error occurs during installation or update, the plugin will log the error and display a message in the WordPress Admin Dashboard.

### Can I add more GitHub plugins to manage?
Yes, you can extend the `$plugin_repositories` array in the plugin's code to include more GitHub plugins.

## Contributing

Contributions are welcome! Please fork this repository and submit a pull request with your changes.

## License

This plugin is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.

## Credits

Developed by [Michele Paolino](https://michelepaolino.me).
