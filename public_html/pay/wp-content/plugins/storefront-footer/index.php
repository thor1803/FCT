<?php
/**
 * Plugin Name: Storefront Footer Copyright Text
 * Plugin URI: http://www.quadmenu.com/storefront
 * Description: Change the footer credit text for Storefront theme.
 * Version: 1.0.0
 * Author: Storefront Footer Text
 * Author URI: http://www.quadmenu.com
 * License: codecanyon
 * Copyright: 2018 QuadMenu (http://www.quadmenu.com)
 */


if (!function_exists('storefront_credit')) {

    function storefront_credit() {
        ?>
        <div class="site-info">
            <?php
            echo wp_kses_post($GLOBALS['storefront_footer']['footer_credit']);
            ?>
        </div><!-- .site-info -->
        <?php
    }

}

if (!class_exists('Storefront_Footer')) {

    class Storefront_Footer {

        private $options;

        public function __construct() {
            add_action('init', array($this, 'options'));
            add_action('admin_menu', array($this, 'add_plugin_page'));
            add_action('admin_init', array($this, 'page_init'));
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
        }

        public function options() {

            global $storefront_footer;

            $defaults = array(
                'footer_credit' => '© QuadLayers 2018 <br/> <a href="http://quadmenu.com/storefront" target="_blank" title="QuadMenu - Storefront Mega Menu" rel="author">QuadMenu - Storefront Mega Menu</a>					',
            );

            $storefront_footer = $this->options = wp_parse_args((array) get_option('storefront_footer'), $defaults);
        }

        function add_action_links($links) {

            $links[] = '<a href="' . admin_url('options-general.php?page=storefront-footer') . '">' . esc_html__('Settings', 'quadmenu') . '</a>';

            return $links;
        }

        public function add_plugin_page() {
            add_options_page('Settings Admin', 'Storefront Footer', 'manage_options', 'storefront-footer', array($this, 'create_admin_page'));
        }

        public function create_admin_page() {
            ?>
            <div class="wrap">
                <h1>Storefront Footer</h1>
                <form method="post" action="options.php">
                    <?php
                    // This prints out all hidden setting fields
                    settings_fields('storefront_footer');
                    do_settings_sections('storefront-footer');
                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        }

        public function page_init() {

            register_setting('storefront_footer', 'storefront_footer', array($this, 'sanitize'));

            add_settings_section('setting_section_id', 'Settings', array($this, 'print_section_info'), 'storefront-footer');

            add_settings_field('footer_credit', 'Footer Credit', array($this, 'footer_credit_callback'), 'storefront-footer', 'setting_section_id');

            /*
             * 
             * add_settings_field(
              'title', 'Title', array($this, 'title_callback'), 'storefront-footer', 'setting_section_id'
              ); */
        }

        public function sanitize($input) {

            $new_input = array();

            if (isset($input['footer_credit']))
                $new_input['footer_credit'] = wp_kses_post($input['footer_credit']);

            /*
             * 
             * if (isset($input['title']))
              $new_input['title'] = sanitize_text_field($input['title']); */

            return $new_input;
        }

        public function print_section_info() {
            print 'Enter your settings below:';
        }

        function footer_credit_callback() {
            wp_editor($this->options['footer_credit'], 'footer_credit', array('media_buttons' => false, 'textarea_rows' => 5, 'textarea_name' => 'storefront_footer[footer_credit]'));
        }

        /*
         * 
         * public function footer_credit_callback() {
          printf(
          '<input type="text" id="footer_credit" name="storefront_footer[footer_credit]" value="%s" />', isset($this->options['footer_credit']) ? esc_attr($this->options['footer_credit']) : ''
          );
          }

          public function title_callback() {
          printf(
          '<input type="text" id="title" name="storefront_footer[title]" value="%s" />', isset($this->options['title']) ? esc_attr($this->options['title']) : ''
          );
          } */
    }

    new Storefront_Footer();
}