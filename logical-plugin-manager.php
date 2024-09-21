<?php
/*
Plugin Name: Logical Plugin Manager
Plugin URI: https://yourwebsite.com/plugin-manager
Description: Centralized management for your WordPress plugins on GitHub and WordPress repository.
Version: 1.0
Author: Michele Paolino
Author URI: https://michelepaolino.me
*/

if (!defined('ABSPATH')) {
    exit;
}

// Array of plugins and their GitHub URLs
$plugin_repositories = [
    'Logical SEO Toolbox' => [
        'url' => 'https://github.com/michelediss/wp-logical-seo/archive/refs/heads/main.zip',
        'description' => 'A lightweight SEO plugin to optimize your WordPress site for search engines.',
        'author' => 'Michele Paolino',
        'author_uri' => 'https://michelepaolino.me',
        'version' => '1.0',
        'plugin_uri' => 'https://github.com/michelediss/wp-logical-seo',
    ],
    'Logical Design System' => [
        'url' => 'https://github.com/michelediss/wp-logical-design-system/archive/refs/heads/main.zip',
        'description' => 'Design system for WordPress, including customizable components and templates.',
        'author' => 'Michele Paolino',
        'author_uri' => 'https://michelepaolino.me',
        'version' => '1.0',
        'plugin_uri' => 'https://github.com/michelediss/wp-logical-design-system',
    ],
    // You can add more plugins here if needed
];

// Array of plugins from the WordPress repository
$wp_repository_plugins = [
    'all-in-one-wp-security-and-firewall' => [
        'slug' => 'all-in-one-wp-security-and-firewall',
    ],
    'cookie-law-info' => [
        'slug' => 'cookie-law-info',
    ],
    'updraftplus' => [
        'slug' => 'updraftplus',
    ],
    'contact-form-7' => [
        'slug' => 'contact-form-7',
    ],
    'wp-maintenance' => [
        'slug' => 'wp-maintenance',
    ],
    'advanced-custom-fields' => [
        'slug' => 'advanced-custom-fields',
    ],
    'breadcrumb-navxt' => [
        'slug' => 'breadcrumb-navxt',
    ],
    'classic-editor' => [
        'slug' => 'classic-editor',
    ],
    'koko-analytics' => [
        'slug' => 'koko-analytics',
    ],
    'mailgun' => [
        'slug' => 'mailgun',
    ],
    'media-library-assistant' => [
        'slug' => 'media-library-assistant',
    ],
    'sassy-social-share' => [
        'slug' => 'sassy-social-share',
    ],
];

// Hook for processing the form submission
add_action('admin_post_apply_actions', 'plugin_manager_process_actions');

function plugin_manager_process_actions() {
    if (isset($_POST['action']) && $_POST['action'] === 'apply_actions') {
        global $plugin_repositories, $wp_repository_plugins;

        $github_plugins = !empty($_POST['selected_plugins']) ? $_POST['selected_plugins'] : [];
        $wp_plugins = !empty($_POST['selected_wp_plugins']) ? $_POST['selected_wp_plugins'] : [];

        $messages = [];

        if (isset($_POST['individual_action']) && $_POST['individual_action'] === 'pull' && !empty($github_plugins)) {
            $plugin_name = $github_plugins[0];
            $plugin_data = $plugin_repositories[$plugin_name];

            $result = plugin_manager_install_or_update_plugin($plugin_name, $plugin_data['url']);

            if (is_wp_error($result)) {
                error_log("Error installing $plugin_name: " . $result->get_error_message());
                $messages[] = "Error installing $plugin_name.";
            } else {
                $messages[] = "$plugin_name installed/updated successfully.";
            }
        } elseif (isset($_POST['individual_action']) && $_POST['individual_action'] === 'install_wp_plugin' && !empty($wp_plugins)) {
            $plugin_slug = $wp_plugins[0];

            $result = plugin_manager_install_wp_plugin($plugin_slug);

            if (is_wp_error($result)) {
                error_log("Error installing $plugin_slug: " . $result->get_error_message());
                $messages[] = "Error installing $plugin_slug.";
            } else {
                $messages[] = "$plugin_slug installed successfully.";
            }
        } else {
            // Bulk actions
            $github_success = plugin_manager_handle_bulk_pull($github_plugins);
            $wp_success = plugin_manager_handle_bulk_wp_install($wp_plugins);

            if ($github_success) {
                $messages[] = 'GitHub plugins installed/updated successfully.';
            } else {
                $messages[] = 'Error installing/updating GitHub plugins. Check debug log for details.';
            }

            if ($wp_success) {
                $messages[] = 'WordPress repository plugins installed successfully.';
            } else {
                $messages[] = 'Error installing WordPress repository plugins. Check debug log for details.';
            }
        }

        if (!empty($messages)) {
            update_option('plugin_manager_messages', $messages);
        }

        wp_redirect(admin_url('options-general.php?page=plugin-manager'));
        exit;
    }
}

function plugin_manager_handle_bulk_pull($selected_plugins) {
    global $plugin_repositories;
    $success = true;

    foreach ($selected_plugins as $plugin_name) {
        if (isset($plugin_repositories[$plugin_name])) {
            $result = plugin_manager_install_or_update_plugin($plugin_name, $plugin_repositories[$plugin_name]['url']);
            if (is_wp_error($result)) {
                error_log("Error installing $plugin_name: " . $result->get_error_message());
                $success = false;
            } else {
                error_log("$plugin_name installed successfully.");
            }
        } else {
            error_log("Plugin $plugin_name not found in repositories.");
            $success = false;
        }
    }

    return $success;
}

function plugin_manager_handle_bulk_wp_install($selected_plugins) {
    $success = true;

    foreach ($selected_plugins as $plugin_slug) {
        $result = plugin_manager_install_wp_plugin($plugin_slug);
        if (is_wp_error($result)) {
            error_log("Error installing $plugin_slug: " . $result->get_error_message());
            $success = false;
        } else {
            error_log("$plugin_slug installed successfully.");
        }
    }

    return $success;
}

// Silent Upgrader Skin
if (!class_exists('Silent_Upgrader_Skin')) {
    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    class Silent_Upgrader_Skin extends WP_Upgrader_Skin {
        public function header() {}
        public function footer() {}
        public function error($errors) {
            if (is_string($errors)) {
                $this->errors[] = $errors;
            } elseif (is_wp_error($errors)) {
                foreach ($errors->get_error_messages() as $message) {
                    $this->errors[] = $message;
                }
            }
        }
        public function feedback($string, ...$args) {}
    }
}

// Function to install a plugin from the WordPress repository
function plugin_manager_install_wp_plugin($plugin_slug) {
    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    $skin = new Silent_Upgrader_Skin();
    $upgrader = new Plugin_Upgrader($skin);

    // Suppress errors if connection to WordPress.org fails
    $result = @$upgrader->install('https://downloads.wordpress.org/plugin/' . $plugin_slug . '.zip');

    if ($result === true || !is_wp_error($result)) {
        return true;
    } else {
        return $result;
    }
}

// Add a menu item under settings
add_action('admin_menu', 'plugin_manager_menu');
function plugin_manager_menu() {
    add_options_page(
        'Logical Plugin Manager',
        'Logical Plugin Manager',
        'manage_options',
        'plugin-manager',
        'plugin_manager_page'
    );
}

// Plugin management page
function plugin_manager_page() {
    global $plugin_repositories, $wp_repository_plugins;

    // Retrieve and display messages
    $messages = get_option('plugin_manager_messages', []);
    if (!empty($messages)) {
        foreach ($messages as $message) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }
        delete_option('plugin_manager_messages');
    }

    ?>
    <div class="wrap">
        <h1>Logical Plugin Manager</h1>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="apply_actions">
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <input type="submit" id="doaction" class="button action" value="Apply">
                </div>
                <br class="clear">
            </div>

            <h2>Logical Stuff Plugins</h2>
            <table class="wp-list-table widefat plugins">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <input id="cb-select-all-1" type="checkbox">
                            <label for="cb-select-all-1"><span class="screen-reader-text">Select All</span></label>
                        </td>
                        <th scope="col" id="name" class="manage-column column-name column-primary">Plugin</th>
                        <th scope="col" id="description" class="manage-column column-description">Description</th>
                        <th scope="col" id="pull-action" class="manage-column column-pull">Pull</th>
                    </tr>
                </thead>
                <tbody id="the-list">
                    <?php foreach ($plugin_repositories as $plugin_name => $plugin_data): ?>
                        <?php
                        $plugin_file = plugin_manager_get_plugin_file($plugin_name);
                        $is_active = is_plugin_active($plugin_file);
                        $row_class = $is_active ? 'active' : 'inactive';
                        ?>
                        <tr class="<?php echo $row_class; ?>" data-slug="<?php echo sanitize_title($plugin_name); ?>" data-plugin="<?php echo esc_attr($plugin_file); ?>">
                            <th scope="row" class="check-column">
                                <label class="label-covers-full-cell" for="checkbox_<?php echo md5($plugin_file); ?>">
                                    <span class="screen-reader-text">Select <?php echo esc_html($plugin_name); ?></span>
                                </label>
                                <input type="checkbox" name="selected_plugins[]" value="<?php echo esc_attr($plugin_name); ?>" id="checkbox_<?php echo md5($plugin_file); ?>">
                            </th>
                            <td class="plugin-title column-primary">
                                <strong><?php echo esc_html($plugin_name); ?></strong>
                                <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
                            </td>
                            <td class="column-description desc">
                                <div class="plugin-description"><p><?php echo esc_html($plugin_data['description']); ?></p></div>
                                <div class="<?php echo $row_class; ?> second plugin-version-author-uri">Version <?php echo esc_html($plugin_data['version']); ?> | By <a href="<?php echo esc_url($plugin_data['author_uri']); ?>"><?php echo esc_html($plugin_data['author']); ?></a> | <a href="<?php echo esc_url($plugin_data['plugin_uri']); ?>" aria-label="Visit plugin site for <?php echo esc_html($plugin_name); ?>">Visit plugin site</a></div>
                            </td>
                            <td class="column-pull">
                                <button type="button" class="button" onclick="plugin_manager_pull_plugin('<?php echo esc_js($plugin_name); ?>')">Pull</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <input id="cb-select-all-2" type="checkbox">
                            <label for="cb-select-all-2"><span class="screen-reader-text">Select All</span></label>
                        </td>
                        <th scope="col" class="manage-column column-name column-primary">Plugin</th>
                        <th scope="col" class="manage-column column-description">Description</th>
                        <th scope="col" class="manage-column column-pull">Pull</th>
                    </tr>
                </tfoot>
            </table>

            <h2>WordPress Repository Plugins</h2>
            <table class="wp-list-table widefat plugins">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <input id="cb-select-all-wp-1" type="checkbox">
                            <label for="cb-select-all-wp-1"><span class="screen-reader-text">Select All</span></label>
                        </td>
                        <th scope="col" class="manage-column column-name column-primary">Plugin</th>
                        <th scope="col" class="manage-column column-description">Description</th>
                        <th scope="col" class="manage-column column-install">Install</th>
                    </tr>
                </thead>
                <tbody id="the-wp-list">
                    <?php foreach ($wp_repository_plugins as $plugin_slug => $plugin_data): ?>
                        <?php
                        $plugin_info = plugin_manager_get_wp_plugin_info($plugin_slug);
                        $plugin_file = plugin_manager_get_plugin_file_by_slug($plugin_slug);
                        $is_installed = $plugin_file !== null;
                        $is_active = $is_installed && is_plugin_active($plugin_file);
                        $row_class = $is_active ? 'active' : ($is_installed ? 'inactive' : 'not-installed');
                        ?>
                        <tr class="<?php echo $row_class; ?>" data-slug="<?php echo esc_attr($plugin_slug); ?>" data-plugin="<?php echo esc_attr($plugin_slug); ?>">
                            <th scope="row" class="check-column">
                                <label class="label-covers-full-cell" for="checkbox_wp_<?php echo md5($plugin_slug); ?>">
                                    <span class="screen-reader-text">Select <?php echo esc_html($plugin_info['name']); ?></span>
                                </label>
                                <input type="checkbox" name="selected_wp_plugins[]" value="<?php echo esc_attr($plugin_slug); ?>" id="checkbox_wp_<?php echo md5($plugin_slug); ?>">
                            </th>
                            <td class="plugin-title column-primary">
                                <strong><?php echo esc_html($plugin_info['name']); ?></strong>
                                <button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
                            </td>
                            <td class="column-description desc">
                                <div class="plugin-description"><p><?php echo esc_html($plugin_info['short_description']); ?></p></div>
                                <div class="<?php echo $row_class; ?> second plugin-version-author-uri">Version <?php echo esc_html($plugin_info['version']); ?> | By <a href="<?php echo esc_url($plugin_info['author_profile']); ?>"><?php echo esc_html($plugin_info['author']); ?></a> | <a href="<?php echo esc_url($plugin_info['homepage']); ?>" aria-label="Visit plugin site for <?php echo esc_html($plugin_info['name']); ?>">Visit plugin site</a></div>
                            </td>
                            <td class="column-install">
                                <?php if ($is_active): ?>
                                    <span>Active</span>
                                <?php elseif ($is_installed): ?>
                                    <button type="button" class="button" onclick="plugin_manager_activate_plugin('<?php echo esc_js($plugin_slug); ?>')">Activate</button>
                                <?php else: ?>
                                    <button type="button" class="button" onclick="plugin_manager_install_wp_plugin('<?php echo esc_js($plugin_slug); ?>')">Install</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <input id="cb-select-all-wp-2" type="checkbox">
                            <label for="cb-select-all-wp-2"><span class="screen-reader-text">Select All</span></label>
                        </td>
                        <th scope="col" class="manage-column column-name column-primary">Plugin</th>
                        <th scope="col" class="manage-column column-description">Description</th>
                        <th scope="col" class="manage-column column-install">Install</th>
                    </tr>
                </tfoot>
            </table>
        </form>
    </div>

    <script>
        // Function for "Select All" checkbox
        document.getElementById('cb-select-all-1').addEventListener('click', function(event) {
            var checkboxes = document.querySelectorAll('input[name="selected_plugins[]"]');
            for (var checkbox of checkboxes) {
                checkbox.checked = event.target.checked;
            }
        });
        document.getElementById('cb-select-all-2').addEventListener('click', function(event) {
            var checkboxes = document.querySelectorAll('input[name="selected_plugins[]"]');
            for (var checkbox of checkboxes) {
                checkbox.checked = event.target.checked;
            }
        });
        document.getElementById('cb-select-all-wp-1').addEventListener('click', function(event) {
            var checkboxes = document.querySelectorAll('input[name="selected_wp_plugins[]"]');
            for (var checkbox of checkboxes) {
                checkbox.checked = event.target.checked;
            }
        });
        document.getElementById('cb-select-all-wp-2').addEventListener('click', function(event) {
            var checkboxes = document.querySelectorAll('input[name="selected_wp_plugins[]"]');
            for (var checkbox of checkboxes) {
                checkbox.checked = event.target.checked;
            }
        });

        // Function for "Pull" button
        function plugin_manager_pull_plugin(plugin_name) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo admin_url('admin-post.php'); ?>';
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_plugins[]';
            input.value = plugin_name;
            form.appendChild(input);

            var action = document.createElement('input');
            action.type = 'hidden';
            action.name = 'action';
            action.value = 'apply_actions';
            form.appendChild(action);

            var individual = document.createElement('input');
            individual.type = 'hidden';
            individual.name = 'individual_action';
            individual.value = 'pull';
            form.appendChild(individual);

            document.body.appendChild(form);
            form.submit();
        }

        // Function for "Install" button
        function plugin_manager_install_wp_plugin(plugin_slug) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo admin_url('admin-post.php'); ?>';
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_wp_plugins[]';
            input.value = plugin_slug;
            form.appendChild(input);

            var action = document.createElement('input');
            action.type = 'hidden';
            action.name = 'action';
            action.value = 'apply_actions';
            form.appendChild(action);

            var individual = document.createElement('input');
            individual.type = 'hidden';
            individual.name = 'individual_action';
            individual.value = 'install_wp_plugin';
            form.appendChild(individual);

            document.body.appendChild(form);
            form.submit();
        }

        // Function for "Activate" button
        function plugin_manager_activate_plugin(plugin_slug) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo admin_url('admin-post.php'); ?>';
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'plugin_slug';
            input.value = plugin_slug;
            form.appendChild(input);

            var action = document.createElement('input');
            action.type = 'hidden';
            action.name = 'action';
            action.value = 'activate_plugin';
            form.appendChild(action);

            document.body.appendChild(form);
            form.submit();
        }
    </script>
    <style>
        .wp-list-table.plugins .not-installed {
            background-color: #f9f9f9; /* Light gray for non-installed plugins */
        }
    </style>
    <?php
}

// Function to get WordPress plugin information from repository with caching
function plugin_manager_get_wp_plugin_info($plugin_slug) {
    $cache_key = 'plugin_info_' . $plugin_slug;
    $plugin_info = get_transient($cache_key);

    if ($plugin_info === false) {
        include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        $api = plugins_api('plugin_information', array(
            'slug' => $plugin_slug,
            'fields' => array(
                'short_description' => true,
                'version' => true,
                'author' => true,
                'author_profile' => true,
                'homepage' => true,
            ),
        ));

        if (is_wp_error($api)) {
            $plugin_info = [
                'name' => ucwords(str_replace('-', ' ', $plugin_slug)),
                'short_description' => 'Description not available.',
                'version' => 'N/A',
                'author' => 'Unknown Author',
                'author_profile' => '',
                'homepage' => '',
            ];
        } else {
            $plugin_info = [
                'name' => $api->name,
                'short_description' => $api->short_description,
                'version' => $api->version,
                'author' => strip_tags($api->author),
                'author_profile' => $api->author_profile,
                'homepage' => $api->homepage,
            ];
        }

        // Cache the data for 12 hours
        set_transient($cache_key, $plugin_info, 12 * HOUR_IN_SECONDS);
    }

    return $plugin_info;
}

// Function to install or update a plugin
function plugin_manager_install_or_update_plugin($plugin_name, $plugin_url) {
    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    $skin = new Silent_Upgrader_Skin();
    $upgrader = new Plugin_Upgrader($skin);

    $result = @$upgrader->install($plugin_url);

    if ($result === true || !is_wp_error($result)) {
        return true;
    } else {
        return $result;
    }
}

// Function to get the plugin file path
function plugin_manager_get_plugin_file($plugin_name) {
    $plugins = get_plugins();
    foreach ($plugins as $plugin_file => $plugin_data) {
        if ($plugin_data['Name'] == $plugin_name) {
            return $plugin_file;
        }
    }
    return null;
}

// Function to get the plugin file path by slug
function plugin_manager_get_plugin_file_by_slug($plugin_slug) {
    $plugins = get_plugins();
    foreach ($plugins as $plugin_file => $plugin_data) {
        $plugin_file_slug = dirname($plugin_file);
        if ($plugin_file_slug === $plugin_slug) {
            return $plugin_file;
        }
    }
    return null;
}

// Schedule a cron job for automatic updates
add_action('wp', 'plugin_manager_schedule_auto_update');
function plugin_manager_schedule_auto_update() {
    if (!wp_next_scheduled('plugin_manager_auto_update')) {
        wp_schedule_event(strtotime('03:33:00'), 'daily', 'plugin_manager_auto_update');
    }
}

add_action('plugin_manager_auto_update', 'plugin_manager_auto_update');
function plugin_manager_auto_update() {
    global $plugin_repositories;

    foreach ($plugin_repositories as $plugin_name => $plugin_data) {
        plugin_manager_install_or_update_plugin($plugin_name, $plugin_data['url']);
    }
}

// Clean up the cron job when the plugin is deactivated
register_deactivation_hook(__FILE__, 'plugin_manager_deactivation');
function plugin_manager_deactivation() {
    wp_clear_scheduled_hook('plugin_manager_auto_update');
}

// Hook for activating a plugin
add_action('admin_post_activate_plugin', 'plugin_manager_activate_plugin');
function plugin_manager_activate_plugin() {
    if (isset($_POST['plugin_slug'])) {
        $plugin_slug = sanitize_text_field($_POST['plugin_slug']);
        $plugin_file = plugin_manager_get_plugin_file_by_slug($plugin_slug);

        if ($plugin_file && !is_plugin_active($plugin_file)) {
            activate_plugin($plugin_file);
            $messages[] = "$plugin_slug activated successfully.";
            update_option('plugin_manager_messages', $messages);
        }
    }

    wp_redirect(admin_url('options-general.php?page=plugin-manager'));
    exit;
}
