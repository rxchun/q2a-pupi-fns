<?php

/*
    This file is part of PUPI - Flexible Notifications System, a
    Question2Answer plugin that allows users to receive notifications in a
    flexible and efficient way.

    Copyright (C) 2024 Gabriel Zanetti <https://github.com/pupi1985>

    PUPI - Flexible Notifications System is free software: you can redistribute
    it and/or modify it under the terms of the GNU General Public License as
    published by the Free Software Foundation, either version 3 of the License,
    or (at your option) any later version.

    PUPI - Flexible Notifications System is distributed in the hope that it
    will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
    of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
    Public License for more details.

    You should have received a copy of the GNU General Public License along
    with PUPI - Flexible Notifications System. If not, see
    <http://www.gnu.org/licenses/>.
*/

// CSS & JS Patch Version
define('FNS_FRONTEND_VERSION', '?v=3');

class qa_html_theme_layer extends qa_html_theme_base
{
    public function initialize()
    {
        parent::initialize();

        if (!qa_is_logged_in()) {
            return;
        }
        
        $this->initialize_fns_cached_points();
    }
    
    function head_custom() {
        qa_html_theme_base::head_custom();
        
        if (qa_is_logged_in())
            $this->addCss();
    }
    
    public function body_hidden()
    {
        qa_html_theme_base::body_hidden();
        
        if (qa_is_logged_in())
            $this->addJsBodyFooter();
    }

    private function addCss()
    {
    
        $this->output('
            <link rel="preload" as="style" href="'.QA_HTML_THEME_LAYER_URLTOROOT.'public/SnowFlat/style.min.css'.FNS_FRONTEND_VERSION.'" onload="this.onload=null;this.rel=\'stylesheet\'">
            <noscript><link rel="stylesheet" href="'.QA_HTML_THEME_LAYER_URLTOROOT.'public/SnowFlat/style.min.css'.FNS_FRONTEND_VERSION.'"></noscript>
        ');
        
        $path = QA_HTML_THEME_LAYER_URLTOROOT . 'public/SnowFlat/fontello/font/';

        $html = sprintf(
            '@font-face {' .
            'font-family: "pupi-fns-fontello";' .
            'src: url("%spupi-fns-fontello.eot?20759465");' .
            'src: url("%spupi-fns-fontello.eot?20759465#iefix") format("embedded-opentype"),' .
            '   url("%spupi-fns-fontello.woff2?20759465") format("woff2"),' .
            '   url("%spupi-fns-fontello.woff?20759465") format("woff"),' .
            '   url("%spupi-fns-fontello.ttf?20759465") format("truetype"),' .
            '   url("%spupi-fns-fontello.svg?20759465#pupi-fns-fontello") format("svg");' .
            'font-weight: normal;' .
            'font-style: normal;' .
            '}',
            $path, $path, $path, $path, $path, $path);

        $this->output(sprintf('<style>%s</style>', $html));
    }
    
    /**
     * @return void
     */
    private function addJsBodyFooter(): void
    {
        $this->output_raw('<script src="'.QA_HTML_THEME_LAYER_URLTOROOT.'public/SnowFlat/ui.min.js'.FNS_FRONTEND_VERSION.'" defer></script>');
    }
    
    public function fns_bell_icon()
    {
        // Get the plugin's base URL dynamically to avoid hardcoding paths in JS.
        // This allows flexibility if the plugin folder is renamed by an admin.
        $pluginUrl = QA_HTML_THEME_LAYER_URLTOROOT;
        
        // Bell icon HTML
        $bellIcon = <<<HTML
            <div class="pupi_fns_notification-icon-container" data-plugin-url="{$pluginUrl}">
                <i class="pupi-fns-icon-bell" data-fetching-data="false"></i>
            </div>
HTML;
        
        /*
            Alternatively, you could use an inline SVG icon instead of the font icon.
            To switch, replace the <i> tag with the following <span> block:
            
            <div class="pupi_fns_notification-icon-container" data-plugin-url="{$pluginUrl}">
                <span class="pupi-fns-icon-bell no-font-icon" data-fetching-data="false">
                    <svg xmlns="http://www.w3.org/2000/svg" data-fetching-data="false" height="24" width="24" viewBox="0 0 50 50"><path d="M8 38v-3h4.2V19.7q0-4.2 2.475-7.475Q17.15 8.95 21.2 8.1V6.65q0-1.15.825-1.9T24 4q1.15 0 1.975.75.825.75.825 1.9V8.1q4.05.85 6.55 4.125t2.5 7.475V35H40v3Zm16-14.75ZM24 44q-1.6 0-2.8-1.175Q20 41.65 20 40h8q0 1.65-1.175 2.825Q25.65 44 24 44Zm-8.8-9h17.65V19.7q0-3.7-2.55-6.3-2.55-2.6-6.25-2.6t-6.275 2.6Q15.2 16 15.2 19.7Z"></path></svg>
                </span>
            </div>
        */
        
        // For legacy themes, append the bell icon to the 'loggedin' user info section.
        $legacyThemes = ['Snow', 'Classic', 'Candy'];
        $currentTheme = qa_opt('site_theme');

        if (in_array($currentTheme, $legacyThemes)) {
            // Check if a suffix already exists before appending.
            $loggedInSuffix = isset($this->content['loggedin']['suffix']) ? $this->content['loggedin']['suffix'] : '';
            $this->content['loggedin']['suffix'] = $loggedInSuffix . ' ' . $bellIcon;
        } else {
            // For modern themes, render the icon directly.
            $this->output($bellIcon);
        }
        
        // Add a mobile-specific container for notifications.
        // Replaces the previous hardcoded JS approach which targeted '.qam-main-nav-wrapper' (SnowFlat-specific).
        $this->output('<div class="fns-mobile-container"></div>');
    }

    public function nav_user_search()
    {
        // Prepend the bell icon to the user navigation
        $this->fns_bell_icon();

        // Continue rendering the original user nav
        qa_html_theme_base::nav_user_search();
    }
    
    public function initialize_fns_cached_points()
    {
        if (!qa_is_logged_in()) {
            return;
        }

        $cache_file = QA_HTML_THEME_LAYER_DIRECTORY . 'cached_points.json';
        $is_admin = qa_get_logged_in_level() >= QA_USER_LEVEL_ADMIN;

        // Generate cached_points.json on page load if it doesn't exist and the user is an admin.
        if ($is_admin && !file_exists($cache_file)) {
            $this->getVotingPoints(true);
        }

        // Update cache if admin clicked "Save/Recalculate" on the Points admin page, or Plugin Options
        $code = qa_post_text('code');
        $getShowAnchor = '?show=' . qa_get('show') . '#' . qa_get('show');
        $valid_security_code = qa_check_form_security_code('admin/points', $code) || qa_check_form_security_code('admin/plugins'.$getShowAnchor, $code);
        $save_clicked = qa_clicked('dosaverecalc') || qa_clicked('pupi_fns_save_button');

        if (
            $this->template === 'admin'
            && $is_admin
            && $valid_security_code
            && $save_clicked
        ) {
            $this->getVotingPoints(true); // force refresh
        }
    }
    
    /**
     * Fetches and caches point settings that define how many points users earn
     * for specific actions, such as upvoting/downvoting questions and answers,
     * or selecting an answer.
     *
     * The settings are retrieved from the database and saved to a JSON cache file
     * that will be created in the plugin’s root directory, for efficient access by the UI.
     *
     * This method is called only when the points system is updated via the Admin > Points page.
     *
     * @param bool $allowDbFetch If false, the method exits immediately without querying the database.
     *                           Should be set to true only when cache regeneration is authorized.
     * @return void|null Returns null if fetching is not allowed; otherwise, nothing.
     */
    private function getVotingPoints($allowDbFetch = false)
    {
        if (!$allowDbFetch) {
            // Not allowed to query DB, exit silently
            return null;
        }
        
        $cache_file = QA_HTML_THEME_LAYER_DIRECTORY . 'cached_points.json';

        $points_title = [
            'points_multiple',
            'points_per_q_voted_up',
            'points_per_q_voted_down',
            'points_per_a_voted_up',
            'points_per_a_voted_down',
            'points_per_c_voted_up',
            'points_per_c_voted_down',
            'points_a_selected',
        ];

        // Fetch fresh data from DB
        $query = qa_db_query_sub(
            'SELECT title, content FROM ^options WHERE title IN (' . implode(',', array_fill(0, count($points_title), '#')) . ')',
            ...$points_title
        );

        $results = qa_db_read_all_assoc($query);
        $points_map = [];

        foreach ($results as $pts) {
            $points_map[$pts['title']] = $pts['content'];
        }

        file_put_contents($cache_file, json_encode($points_map));
        
        // Will load results via ui.js file
        // return $points_map;
    }
        
}
