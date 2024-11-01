<?php

class SwiftSalesIntegrationPlugin
{
    private $websiteId = null;
    private $authToken = null;
    private $accountId = null;
    private $pluginFileName = null;
    private $pluginDirectoryURL = null;

    private const PLUGIN_PREFIX = 'ssi';
    private const WEBSITE_ID_KEY = 'ssi_web_id';
    private const AUTH_TOKEN_KEY = 'ssi_token';
    private const ACCOUNT_ID_KEY = 'ssi_account_id';
    private const INTEGRATION_SCRIPT_URL = 'https://script.swiftchat.io/swiftsales.js?v=1574755144435';

    public function __construct($pluginFileName)
    {
        $this->pluginFileName = $pluginFileName;
        $this->pluginDirectoryURL = plugin_dir_url($this->pluginFileName);
        $this->websiteId = get_option($this::WEBSITE_ID_KEY);
        $this->authToken = get_option($this::AUTH_TOKEN_KEY);
        $this->accountId = get_option($this::ACCOUNT_ID_KEY);
    }

    public  function registerActions()
    {
        register_uninstall_hook($this->pluginFileName, [$this, 'uninstallAction']);

        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);

        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminStyles']);

        add_action('wp_head', [$this, 'printHeadScript']);

        if (is_admin()) {
            add_action('admin_menu', [$this, 'addMenuItem']);
        }

        add_action('wp_ajax_' . $this::PLUGIN_PREFIX . '_post_id', [$this, 'ajaxSaveWebsiteId']);

        add_action('admin_init', [$this, 'addOptions']);

        add_action('wp_ajax_' . $this::PLUGIN_PREFIX . '_login', [$this, 'ajaxSaveAuthCreds']);

        add_action('wp_ajax_' . $this::PLUGIN_PREFIX . '_logout', [$this, 'ajaxRemoveAuthCreds']);
    }

    public  function uninstallAction()
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        delete_option($this::WEBSITE_ID_KEY);
        delete_option($this::AUTH_TOKEN_KEY);
        delete_option($this::ACCOUNT_ID_KEY);
    }

    public function enqueueAdminScripts()
    {
        wp_enqueue_script('ssi_plugin-admin', $this->getScriptUrl('ssi_js.js'), array(), null, false);
    }

    public function enqueueAdminStyles()
    {
        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_style('ssi_plugin-admin', $this->getStyleUrl('ssi_css.css'), array(), null, 'all');
    }

    public function addMenuItem()
    {
        add_menu_page(
            esc_html__('SwiftChat', $this::PLUGIN_PREFIX . '_menu'),
            esc_html__('SwiftChat', $this::PLUGIN_PREFIX . '_menu'),
            'manage_options',
            $this::PLUGIN_PREFIX . '_menu',
            [$this, 'printSettingPageView'],
            'dashicons-admin-generic',
            null
        );
    }

    private function getLoginFormView()
    {
        return '
        <div class="swift-sales-ip-wrapper wrap">
        <div class="content-placeholder">
            <div>
            <h1>Swift Chat</h1>
            <h4>Instructions</h4>
            <p>
                Please login with your swiftchat credentials to connect this website with swiftchat analytics dashboard. If you don\'t have a swiftchat account signup <a href="https://app.swiftchat.io">here</a>. 
            </p>
            </div>
        </div>
        <div class="swift-sales-form-wrapper">
            <form id="' . $this::PLUGIN_PREFIX . '_login_form" >
                <h1>Login</h1>
                <div><br/>
                    <input type="email" id="' . $this::PLUGIN_PREFIX . '_email_input" placeholder="Email" class="all-options" />
                </div><br />
                <div>
                    <input type="password" id="' . $this::PLUGIN_PREFIX . '_password_input" placeholder="Password" class="all-options" />
                </div><br />
                <div class="ssip-submit-btn">
                    <input class="button-primary" type="submit" name="Login" value="Login" />
                </div>
                <input type="hidden" value="' . wp_create_nonce($this::PLUGIN_PREFIX . '_plugin-nonce') . '">
            </form>
        </div>
        </div>';
    }

    private function getWebsiteDropdownView()
    {
        return '
        <div class="swift-sales-ip-wrapper wrap">
            <div class="content-placeholder">
                <div>
                    <h1>Swift Chat</h1>
                    <h4>Instructions</h4>
                    <p>Please select the website from the dropdown below to connect this site to swiftsales analytics dashboard. If you cannot find your website please add a new website from <a href="https://app.swiftchat.io/dashboard/websites">dashboard</a></p>
                </div>
                </div>
                <div class="swift-sales-select-wrapper">
                <form  id="' . $this::PLUGIN_PREFIX . '_website_select_form">
                <h1>Select Website</h1>
                <div>
                <select name="" id="' . $this::PLUGIN_PREFIX . '_website_select" placeholder="Select Website" required>'
            . (!$this->isWebsiteActive() ? '<option value="" selected>Select Website</option>' : "") . '
                </select>
                <input class="button-primary" type="submit" value="Save" />
                <p style="display: none; color: red;" id="' . $this::PLUGIN_PREFIX . '_alertline">Warning! selected website does not matches this website</p>
                </div><br />
                <div class="ssip-submit-btn">
                <input class="button" type="button" id="' . $this::PLUGIN_PREFIX . '_disconnect_btn" value="Disconnect" />
                </div>
                </form>
                <div id="' . $this::PLUGIN_PREFIX . '_data_attr" data-' . $this::PLUGIN_PREFIX . '_token="' . $this->authToken . '" data-' . $this::PLUGIN_PREFIX . '_account_id="' . $this->accountId . '" data-' . $this::PLUGIN_PREFIX . '_website_id="' . $this->websiteId . '">
                </div>
            </div>
        </div>';
    }

    public function printSettingPageView()
    {
        if (!$this->isSSLoggedIn()) {
            echo  $this->getLoginFormView();
        } else {
            echo $this->getWebsiteDropdownView();
        }

        '
            <!-- select dialog -->
            <div style="display: none;" title="Website">

                <label for="month">Websites</label>
                <select name="month" id="' . $this::PLUGIN_PREFIX . '_website_select">
                </select>
                <br>
                <br>
                <p style="display: none; color: red;" id="' . $this::PLUGIN_PREFIX . '_alertline">Your Website Does Not Match The Selected Website</p>
                <input type="submit" class="button button-primary" id="' . $this::PLUGIN_PREFIX . '_save_website_btn" value="Submit">
            </div>';
        '<h1> Swift Chat </h1>
            <p> Change Website Priority</p>
            <div id="' . $this::PLUGIN_PREFIX . '_data_attr" data-' . $this::PLUGIN_PREFIX . '_token="' . $this->authToken . '" data-' . $this::PLUGIN_PREFIX . '-account_id="' . $this->accountId . '" data-' . $this::PLUGIN_PREFIX . '_webid="' . $this->websiteId . '">
            </div>
            <div style="" title="Website">
                <label for="month">Websites</label>
                <select name="month" id="' . $this::PLUGIN_PREFIX . '_website_select">' . ($this->isSSLoggedIn() && !$this->isWebsiteActive()) ? '<option value="0">Select Option</option>' : "" . ' 
                </select>
                <br>
                <p style="display: none; color: red;" id="' . $this::PLUGIN_PREFIX . '_alertline">Your Website Does Not Match The Selected Website</p>
                <input type="submit" class="button button-primary" id="' . $this::PLUGIN_PREFIX . '_save_website_btn" value="Submit">' . $this->isSSLoggedIn() ?
            '<input style=" margin-left: 155px;" type="submit" class="button button-primary" id="' . $this::PLUGIN_PREFIX . '_logout_btn" value="log out">' : "" . '
            </div>';
    }

    public function printHeadScript()
    {
        if ($this->isWebsiteActive()) {
            echo '
            <script type = "text/javascript">
                (function(scope, doc, tagName, src, objectName, newElement, firstElement) {
                    Array.isArray(scope["SwiftSalesObject"]) ?
                        scope["SwiftSalesObject"].push(objectName) :
                        (scope["SwiftSalesObject"] = [objectName]);
                    scope[objectName] =
                        scope[objectName] ||
                        function() {
                            scope[objectName].queries = scope[objectName].queries || [];
                            scope[objectName].queries.push(arguments);
                        };
                    scope[objectName].scriptInjectedAt = 1 * new Date();
                    newElement = doc.createElement(tagName);
                    newElement.setAttribute("id", "swift-sales-widget-script");
                    firstElement = doc.getElementsByTagName(tagName)[0];
                    newElement.async = 1;
                    newElement.src = src;
                    firstElement
                        ?
                        firstElement.parentNode.insertBefore(newElement, firstElement) :
                        doc.getElementsByTagName("head")[0].appendChild(newElement);
                })(window, document, "script", "' . $this::INTEGRATION_SCRIPT_URL . '", "swiftSales");
                swiftSales("Init", "' . $this->websiteId . '"); 
            </script>';
        }
    }

    private function getScriptUrl($scriptName)
    {
        return $this->pluginDirectoryURL . 'public/js/' . $scriptName;
    }

    private function getStyleUrl($styleName)
    {
        return $this->pluginDirectoryURL . 'public/css/' . $styleName;
    }

    public function ajaxSaveWebsiteId()
    {
        $websiteId = sanitize_text_field($_POST['id']);
        update_option($this::WEBSITE_ID_KEY, $websiteId);
        echo $websiteId;
        wp_die();
    }

    public function addOptions()
    {
        add_option($this::WEBSITE_ID_KEY, '');
        add_option($this::ACCOUNT_ID_KEY, '');
        add_option($this::AUTH_TOKEN_KEY, '');
    }

    public function ajaxSaveAuthCreds()
    {
        $authToken = sanitize_text_field($_POST['token']);
        $accountId = sanitize_text_field($_POST['account_id']);
        update_option($this::AUTH_TOKEN_KEY, $authToken);
        update_option($this::ACCOUNT_ID_KEY, $accountId);
        wp_die();
    }

    public function ajaxRemoveAuthCreds()
    {
        update_option($this::AUTH_TOKEN_KEY, '');
        update_option($this::ACCOUNT_ID_KEY, '');
        update_option($this::WEBSITE_ID_KEY, '');

        wp_die();
    }

    private function isWebsiteActive()
    {
        return (bool) trim($this->websiteId);
    }

    private function isSSLoggedIn()
    {
        return (bool) trim($this->authToken) && (bool) trim($this->accountId);
    }
}
