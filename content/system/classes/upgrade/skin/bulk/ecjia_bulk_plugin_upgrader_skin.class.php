<?php
  
class ecjia_bulk_plugin_upgrader_skin extends ecjia_bulk_upgrader_skin {
    public $plugin_info = array(); // Plugin_Upgrader::bulk() will fill this in.
    
    function __construct($args = array()) {
        parent::__construct($args);
    }
    
    function add_strings() {
        parent::add_strings();
        $this->upgrader->strings['skin_before_update_header'] = __('Updating Plugin %1$s (%2$d/%3$d)');
    }
    
    function before($title = '') {
        parent::before($this->plugin_info['Title']);
    }
    
    function after($title = '') {
        parent::after($this->plugin_info['Title']);
        $this->decrement_update_count( 'plugin' );
    }
    
    function bulk_footer() {
        parent::bulk_footer();
        $update_actions =  array(
            'plugins_page' => '<a href="' . self_admin_url('plugins.php') . '" title="' . esc_attr__('Go to plugins page') . '" target="_parent">' . __('Return to Plugins page') . '</a>',
            'updates_page' => '<a href="' . self_admin_url('update-core.php') . '" title="' . esc_attr__('Go to WordPress Updates page') . '" target="_parent">' . __('Return to WordPress Updates') . '</a>'
        );
        if ( ! current_user_can( 'activate_plugins' ) )
            unset( $update_actions['plugins_page'] );
    
        /**
         * Filter the list of action links available following bulk plugin updates.
         *
         * @since 3.0.0
         *
         * @param array $update_actions Array of plugin action links.
         * @param array $plugin_info    Array of information for the last-updated plugin.
        */
        $update_actions = RC_Hook::apply_filters( 'update_bulk_plugins_complete_actions', $update_actions, $this->plugin_info );
    
        if ( ! empty($update_actions) ) {
            $this->feedback(implode(' | ', (array)$update_actions));
        }
    }
    
}

// end