<?php

    /**
     * wp-login-mod
     *
     * Plugin Name:       wp-login-mod
     * Description:       This easy-to-use WordPress plugin forces user login by redirecting all visitors to the login page, effectively turning your site into a private, login-only landing page. You can also prevent new users from registering, add a message on the login page, and include your own CSS and JS.
     * Version:           1.00.0
     * Author:            keytonic
     * Author URI:        https://www.keytonic.net
     * License:           GNU General Public License v3.0
     * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
     */

    function wpLoaded() 
    {
        //if admin access only is turned off bail
        if(get_option('wp-login-mod-options')['adm'] != "1") return;

        $current_user = wp_get_current_user();

        //!current_user_can('administrator')
        if (is_user_logged_in()  &&  !in_array('administrator', $current_user->roles))
        {
            //wp_logout();
            wp_redirect('https://' . $_SERVER['HTTP_HOST'] . "/wp-login.php?action=disabled");
        }
    }
    function loginMessage($message) 
    {
        $action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';

        if($action == "disabled")
        {
            $message = '<div id="login_error">Login Temporarily Disabled.</div>';
            wp_logout();
        }

        return $message;
    }
    function forceLogin()
    {
        //if force login is turned off bail
        if(get_option('wp-login-mod-options')['log'] != "1") return;

        //making sure they arent logged in
        if (!is_user_logged_in()) 
        {
            //prevent redirecting the user to a page they are already on
            if(basename($_SERVER['SCRIPT_FILENAME'],".php") != "wp-login")
            {
                //send it
                wp_redirect( 'https://' . $_SERVER['HTTP_HOST'] . "/wp-login.php" );
            }
        }
    }
    function loginHead()
    {
        //retrieving the custom scripts
        $css = get_option('wp-login-mod-options')['css'];
        $js = get_option('wp-login-mod-options')['js'];
        //if they arent empty, inject them
        if($css != "") echo '<style type="text/css"> '. $css . '</style>';
        if($js != "") echo '<script>' . $js . '</script>';
    }
    function loginFooter()
    {
        //if show message is turned off bail
        if(get_option('wp-login-mod-options')['sho'] != "1") return;

        //retrive message
        $msg = (isset(get_option('wp-login-mod-options')['msg']) ? get_option('wp-login-mod-options')['msg'] : "");
 
        //if there is a message, inject it
        if($msg != "")
        {
            echo '
            <script>
            let newNode = document.createElement("p");
            newNode.classList.add("message");
            newNode.innerHTML = `'.$msg.'`;
            let login = document.getElementById("login");
            login.insertBefore(newNode, login.children[1]);
            </script>';
        }
    }
    //adding a "Settings" link next to the activate/deactivate links of my plugin in the admin plugins page
    function pluginActionLinks($actions) 
    {
        $mylinks = array('<a href="' . admin_url( 'options-general.php?page=wp-login-mod-admin' ) . '">Settings</a>');
        $actions = array_merge( $actions, $mylinks );
        return $actions;
    }
    //callback function for add_action to 'admin_menu' that will create options page selectable under settings
    function addOptionsPage()
    {
        add_options_page('wp-login-mod','wp-login-mod','manage_options','wp-login-mod-admin', 'createOptionsPage' );
    }
    //callback function for add_options_page to generate the html for my new options page
    function createOptionsPage()
    {
        ?>
            <div class="wrap">
                <h1>wp-login-mod</h1>
                <form method="post" action="options.php">
                    <?php
                        settings_fields( 'wp-login-mod-group' );
                        do_settings_sections( 'wp-login-mod-admin' );
                        submit_button();
                    ?>
                </form>
            </div>
        <?php
    }
    //callback function for add_action to 'admin_init' that will initialize my plugin options page
    function pageInit()
    {
        register_setting('wp-login-mod-group','wp-login-mod-options', 'sanitize');
        add_settings_section('sectionOne','','sectionTitle' , 'wp-login-mod-admin');  
        add_settings_field('reg','Disable new user registration.','reg_callback' ,'wp-login-mod-admin','sectionOne');
        add_settings_field('log','Require login to view site.','log_callback' ,'wp-login-mod-admin','sectionOne');
        add_settings_field('adm','Restrict login access to admin accounts only.','adm_callback' ,'wp-login-mod-admin','sectionOne');
        add_settings_field('sho','Show Login page message.','sho_callback' ,'wp-login-mod-admin','sectionOne');
        add_settings_field('msg','Login page message:','msg_callback' ,'wp-login-mod-admin','sectionOne');
        add_settings_field('css','CSS:','css_callback' ,'wp-login-mod-admin','sectionOne');
        add_settings_field('js','JS:', 'js_callback' ,'wp-login-mod-admin','sectionOne');
    }
    //Sanitize each setting field as needed, $input Contains all settings fields as array keys
    function sanitize( $input )
    {
        $new_input = array();
        if(isset( $input['msg'] )) $new_input['msg'] = wp_kses_post($input['msg']);
        if(isset( $input['css'])) $new_input['css'] = sanitize_textarea_field($input['css']);
        if(isset( $input['js'] )) $new_input['js'] = sanitize_textarea_field($input['js']);
        isset($input['reg']) ? $new_input['reg'] = "1" : $new_input['reg'] = "0";
        isset($input['log']) ? $new_input['log'] = "1" : $new_input['log'] = "0";
        isset($input['sho']) ? $new_input['sho'] = "1" : $new_input['sho'] = "0";
        isset($input['adm']) ? $new_input['adm'] = "1" : $new_input['adm'] = "0";
        return $new_input;
    }
    //call back functions for settings on options page
    function sectionTitle()
    {
        print 'Set your preferences below:';
    }
    function reg_callback()
    {
        $enabled = get_option('wp-login-mod-options')['reg'];
        echo '<input type="checkbox" id="reg" name="wp-login-mod-options[reg]" value="1" '. (($enabled == "1") ? "checked" : "") .'>';
    }
    function log_callback()
    {
        $enabled = get_option('wp-login-mod-options')['log'];
        echo '<input type="checkbox" id="log" name="wp-login-mod-options[log]" value="1" '. (($enabled == "1") ? "checked" : "") .'>';
    }
    function sho_callback()
    {
        $enabled = get_option('wp-login-mod-options')['sho'];
        echo '<input type="checkbox" id="sho" name="wp-login-mod-options[sho]" value="1" '. (($enabled == "1") ? "checked" : "") .'>';
    }
    function adm_callback()
    {
        $enabled = get_option('wp-login-mod-options')['adm'];
        echo '<input type="checkbox" id="adm" name="wp-login-mod-options[adm]" value="1" '. (($enabled == "1") ? "checked" : "") .'>';
    }
    function msg_callback()
    {
        $msg = (isset(get_option('wp-login-mod-options')['msg']) ? esc_attr(get_option('wp-login-mod-options')['msg']) : "");
        printf('<textarea id="msg" name="wp-login-mod-options[msg]" rows="4" cols="100">%s</textarea>', $msg);
    }
    function css_callback()
    {
        $css = (isset(get_option('wp-login-mod-options')['css']) ? esc_attr(get_option('wp-login-mod-options')['css']) : "");
        printf('<textarea id="css" name="wp-login-mod-options[css]" rows="8" cols="100">%s</textarea>', $css);
    }
    function js_callback()
    {
        $js = (isset(get_option('wp-login-mod-options')['js']) ? esc_attr(get_option('wp-login-mod-options')['js']) : "");
        printf('<textarea id="js" name="wp-login-mod-options[js]" rows="8" cols="100">%s</textarea>', $js);
    }
    //set the hooks
    if(get_option('wp-login-mod-options')['reg'] == "1")
    {
        update_option( 'users_can_register', 0 );
    }
    add_action('wp_loaded', 'wpLoaded');
    add_action('init', 'forceLogin');
    add_action('login_head', 'loginHead');//Fires in the login page header after scripts are enqueued.
    add_action('login_footer', 'loginFooter');//Fires in the login page footer.
    add_action('admin_menu','addOptionsPage');
    add_action('admin_init','pageInit');
    add_filter('plugin_action_links_' . plugin_basename(__FILE__),'pluginActionLinks');
    add_filter('login_message', 'loginMessage',10,1);
?>