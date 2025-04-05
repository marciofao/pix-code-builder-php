<?php
// Add menu to admin sidebar
add_action('admin_menu', 'my_admin_menu');

function my_admin_menu() {
    add_menu_page(
        __('PIX code builder', 'pix-code-builder'), // Page title
        __('PIX code builder', 'pix-code-builder'), // Menu title
        'manage_options', // Capability
        'pix-code-builder-settings', // Menu slug
        'pcb_custom_options_page', // Function to display the page
        'dashicons-admin-generic', // Icon
        100 // Position
    );
}

// Register settings
add_action('admin_init', 'register_custom_settings');

function register_custom_settings() {
    register_setting('custom-settings-group', 'pcb_pix_key');
    register_setting('custom-settings-group', 'pcb_button_text');
    register_setting('custom-settings-group', 'pcb_value_css_selector');
}

// Create the options page
function pcb_custom_options_page() {
    ?>
    <h3><?php _e('Shortcode:','pix-code-builder') ?></h3>
    <h4>[pcb_pix_button idTransacao="12345"]</h4>
    <div class="wrap">
        <h2><?php _e('Custom Options', 'pix-code-builder'); ?></h2>
        <form method="post" action="options.php">
            <?php settings_fields('custom-settings-group'); ?>
            <?php do_settings_sections('custom-settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Chave Pix', 'pix-code-builder'); ?></th>
                    <td><input placeholder="user@example.com" type="text" name="pcb_pix_key" value="<?php echo esc_attr(get_option('pcb_pix_key')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Texto botão', 'pix-code-builder'); ?></th>
                    <td><input placeholder="Pagar por pix" type="text" name="pcb_button_text" value="<?php echo esc_attr(get_option('pcb_button_text')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('CSS Selector', 'pix-code-builder'); ?></th>
                    <td><input placeholder=".cart-total" type="text" name="pcb_value_css_selector" value="<?php echo esc_attr(get_option('pcb_value_css_selector')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
