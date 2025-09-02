<?php
namespace EFC;
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Admin {
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_post_efc_pro_reset', [ __CLASS__, 'handle_reset' ] );
        add_action( 'admin_post_efc_pro_save_settings', [ __CLASS__, 'save_settings' ] );
        add_action( 'wp_dashboard_setup', [ __CLASS__, 'add_dashboard_widget' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
    }

    public static function enqueue_admin_assets() {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }

    public static function register_menu() {
        add_options_page('Form Counter Pro','Form Counter Pro','manage_options','efc-pro-settings',[__CLASS__,'render_page']);
    }

    public static function render_page() {
        if ( ! current_user_can('manage_options') ) return;

        $forms = get_option(Counter::opt_forms_index_key(), array());
        if ( ! is_array($forms) ) $forms = array();
        $settings = get_option('efc_pro_settings', array());

        // Shortcodes list
        $shortcodes = [
            '[form_counter form="Waitlist"]' => 'Default counter for Waitlist form',
            '[form_counter form="Waitlist" type="progress" target="100"]' => 'Progress bar example for Waitlist with target 100',
            '[form_counter form="Waitlist" style="bold" font_size="24px" font_color="#ff0000"]' => 'Bold style with font size and color override',
            '[form_counter form="Waitlist" style="animated"]' => 'Animated counter style example'
        ];
        ?>
        <div class="wrap">
            <h1>Elementor Form Counter Pro</h1>
            <h2 class="nav-tab-wrapper">
                <a href="#general-settings" class="nav-tab nav-tab-active">General Settings</a>
                <a href="#shortcodes" class="nav-tab">Shortcodes</a>
            </h2>

            <div id="general-settings" class="efc-tab-content" style="display:block;">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="efc_pro_save_settings" />
                    <?php wp_nonce_field('efc_pro_save_settings'); ?>
                    <table class="form-table">
                        <tr>
                            <th>Default template</th>
                            <td>
                                <select name="settings[default_template]">
                                    <option value="minimal" <?php selected($settings['default_template'] ?? '', 'minimal'); ?>>Minimal</option>
                                    <option value="bold" <?php selected($settings['default_template'] ?? '', 'bold'); ?>>Bold</option>
                                    <option value="animated" <?php selected($settings['default_template'] ?? '', 'animated'); ?>>Animated</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Default font size</th>
                            <td><input type="text" name="settings[default_font_size]" value="<?php echo esc_attr($settings['default_font_size'] ?? ''); ?>" placeholder="e.g., 24px" /></td>
                        </tr>
                        <tr>
                            <th>Default font color</th>
                            <td>
                                <input type="text" name="settings[default_font_color]" value="<?php echo esc_attr($settings['default_font_color'] ?? ''); ?>" class="efc-color-picker" placeholder="e.g., #000000" />
                                <span id="efc-color-preview" style="display:inline-block;width:30px;height:20px;background:<?php echo esc_attr($settings['default_font_color'] ?? '#000'); ?>;margin-left:5px;border:1px solid #ccc;"></span>
                            </td>
                        </tr>
                        <tr>
                            <th>Default font family</th>
                            <td><input type="text" name="settings[default_font_family]" value="<?php echo esc_attr($settings['default_font_family'] ?? ''); ?>" placeholder="e.g., Arial, sans-serif" /></td>
                        </tr>
                    </table>
                    <p><button class="button button-primary" type="submit">Save settings</button></p>
                </form>

                <h2>Tracked forms</h2>
                <?php if ( empty($forms) ) : ?>
                    <p>No forms tracked yet.</p>
                <?php else: ?>
                    <table class="widefat striped">
                        <thead>
                            <tr><th>Form</th><th>Slug</th><th>Count</th><th>Emails</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach($forms as $slug):
                            $label = get_option('efc_pro_label_'.$slug,$slug);
                            $count = (int)get_option(Counter::opt_count_key($slug),0);
                            $emails = get_option(Counter::opt_emails_key($slug),array());
                            if(!is_array($emails)) $emails=array();
                        ?>
                            <tr>
                                <td><?php echo esc_html($label); ?></td>
                                <td><code><?php echo esc_html($slug); ?></code></td>
                                <td><?php echo esc_html($count); ?></td>
                                <td><?php echo esc_html(count($emails)); ?></td>
                                <td>
                                    <?php
                                    $export = wp_nonce_url(admin_url('admin-post.php?action=efc_pro_export&slug='.urlencode($slug)),'efc_pro_export_' . $slug);
                                    $reset  = wp_nonce_url(admin_url('admin-post.php?action=efc_pro_reset&slug='.urlencode($slug)),'efc_pro_reset_' . $slug);
                                    ?>
                                    <a class="button" href="<?php echo esc_url($export); ?>">Export CSV</a>
                                    <a class="button button-secondary" href="<?php echo esc_url($reset); ?>" onclick="return confirm('Reset this form?');">Reset</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p><a class="button button-primary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=efc_pro_reset&slug=__all__'),'efc_pro_reset___all__')); ?>" onclick="return confirm('Reset ALL?');">Reset All</a></p>
                <?php endif; ?>
            </div>

            <div id="shortcodes" class="efc-tab-content" style="display:none;">
                <h2>Available Shortcodes</h2>
                <table class="widefat striped">
                    <thead><tr><th>Shortcode</th><th>Description</th><th>Copy</th></tr></thead>
                    <tbody>
                    <?php foreach($shortcodes as $code => $desc): ?>
                        <tr>
                            <td><code><?php echo esc_html($code); ?></code></td>
                            <td><?php echo esc_html($desc); ?></td>
                            <td><button class="button efc-copy-btn" data-shortcode="<?php echo esc_attr($code); ?>">Copy</button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($){
            // Tabs switching
            $('.nav-tab').click(function(e){
                e.preventDefault();
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                $('.efc-tab-content').hide();
                $($(this).attr('href')).show();
            });

            // Color picker
            $('.efc-color-picker').wpColorPicker({
                change: function(event, ui){
                    $('#efc-color-preview').css('background', ui.color.toString());
                },
                clear: function(){
                    $('#efc-color-preview').css('background', '#000');
                }
            });

            // Copy to clipboard
            $('.efc-copy-btn').click(function(){
                var code = $(this).data('shortcode');
                navigator.clipboard.writeText(code).then(function(){
                    alert('Shortcode copied: ' + code);
                });
            });
        });
        </script>
        <?php
    }

    public static function save_settings() {
        if ( ! current_user_can('manage_options') ) wp_die('Forbidden',403);
        if ( ! check_admin_referer('efc_pro_save_settings') ) wp_die('Invalid nonce',403);
        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
        update_option('efc_pro_settings', $settings);
        wp_safe_redirect(admin_url('options-general.php?page=efc-pro-settings&saved=1'));
        exit;
    }

    public static function handle_reset() {
        if ( ! current_user_can('manage_options') ) wp_die('Forbidden',403);
        $slug = isset($_GET['slug']) ? sanitize_key(wp_unslash($_GET['slug'])) : '';
        if ( empty($slug) ) { wp_safe_redirect(admin_url('options-general.php?page=efc-pro-settings')); exit; }
        if ( ! wp_verify_nonce($_GET['_wpnonce'] ?? '', 'efc_pro_reset_' . $slug) ) wp_die('Invalid nonce');
        if ( '__all__' === $slug ) {
            $forms = get_option(Counter::opt_forms_index_key(), array());
            if ( is_array($forms) ) {
                foreach($forms as $s) {
                    delete_option(Counter::opt_count_key($s));
                    delete_option(Counter::opt_emails_key($s));
                    delete_option('efc_pro_label_' . $s);
                }
            }
            delete_option(Counter::opt_forms_index_key());
        } else {
            delete_option(Counter::opt_count_key($slug));
            delete_option(Counter::opt_emails_key($slug));
            delete_option('efc_pro_label_' . $slug);
            $forms = get_option(Counter::opt_forms_index_key(), array());
            if (is_array($forms)) {
                $forms = array_values(array_diff($forms, array($slug)));
                update_option(Counter::opt_forms_index_key(), $forms);
            }
        }
        wp_safe_redirect(admin_url('options-general.php?page=efc-pro-settings&reset=1')); exit;
    }

    public static function add_dashboard_widget() {
        wp_add_dashboard_widget('efc_pro_dashboard','Form Counter Pro', [__CLASS__,'dashboard_widget']);
    }

    public static function dashboard_widget() {
        $forms = get_option(Counter::opt_forms_index_key(), array());
        if ( ! is_array($forms) ) $forms = array();
        echo '<div>';
        if ( empty($forms) ) {
            echo '<p>No forms tracked yet.</p>';
        } else {
            echo '<ul>';
            foreach($forms as $slug){
                $label = get_option('efc_pro_label_'.$slug,$slug);
                $count = (int)get_option(Counter::opt_count_key($slug),0);
                echo '<li><strong>'.esc_html($label).':</strong> '.esc_html($count).'</li>';
            }
            echo '</ul>';
        }
        echo '</div>';
    }
}

Admin::init();
