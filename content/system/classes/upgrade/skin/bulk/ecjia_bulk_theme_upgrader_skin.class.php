<?php
  
class ecjia_bulk_theme_upgrader_skin extends ecjia_bulk_upgrader_skin {
    public $theme_info = array(); // Theme_Upgrader::bulk() will fill this in.
    
    function __construct($args = array()) {
        parent::__construct($args);
    }
    
    function add_strings() {
        parent::add_strings();
        $this->upgrader->strings['skin_before_update_header'] = __('Updating Theme %1$s (%2$d/%3$d)');
    }
    
    function before($title = '') {
        parent::before( $this->theme_info->display('Name') );
    }
    
    function after($title = '') {
        parent::after( $this->theme_info->display('Name') );
        $this->decrement_update_count( 'theme' );
    }
    
    
    function bulk_footer() {
        parent::bulk_footer();
        $update_actions =  array(
            'themes_page' => '<a href="' . self_admin_url('themes.php') . '" title="' . esc_attr__('Go to themes page') . '" target="_parent">' . __('Return to Themes page') . '</a>',
            'updates_page' => '<a href="' . self_admin_url('update-core.php') . '" title="' . esc_attr__('Go to WordPress Updates page') . '" target="_parent">' . __('Return to WordPress Updates') . '</a>'
        );
        if ( ! current_user_can( 'switch_themes' ) && ! current_user_can( 'edit_theme_options' ) )
            unset( $update_actions['themes_page'] );
    
        /**
         * Filter the list of action links available following bulk theme updates.
         *
         * @since 3.0.0
         *
         * @param array $update_actions Array of theme action links.
         * @param array $theme_info     Array of information for the last-updated theme.
        */
        $update_actions = RC_Hook::apply_filters( 'update_bulk_theme_complete_actions', $update_actions, $this->theme_info );
    
        if ( ! empty($update_actions) ) {
            $this->feedback(implode(' | ', (array)$update_actions));
        }
    }
    
}

// end