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

class qa_html_theme_layer extends qa_html_theme_base
{
    public function initialize()
    {
        parent::initialize();

        if (!qa_is_logged_in()) {
            return;
        }

        $this->addCss();
        $this->addJsBodyFooter();
    }

    private function addCss()
    {
        if (!isset($this->content['body_header'])) {
            $this->content['body_header'] = '';
        }

        $path = QA_HTML_THEME_LAYER_URLTOROOT . 'public/SnowFlat/fontello/font/';

        $html = file_get_contents(QA_HTML_THEME_LAYER_DIRECTORY . 'public/SnowFlat/style.min.css');
        $html .= sprintf(
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

        $this->content['body_header'] .= sprintf('<style>%s</style>', $html);
    }
    
    public function fns_bell_icon()
    {
        // Bell icon HTML
        $bellIcon = <<<HTML
            <div class="pupi_fns_notification-icon-container">
                <i class="pupi-fns-icon-bell" data-fetching-data="false"></i>
            </div>
HTML;

        // Add the icon as a suffix to the logged-in user data for legacy themes
        $legacyThemes = ['Snow', 'Classic', 'Candy'];
        $currentTheme = qa_opt('site_theme');

        if (in_array($currentTheme, $legacyThemes)) {
            // Ensure suffix exists before appending
            $loggedInSuffix = isset($this->content['loggedin']['suffix']) ? $this->content['loggedin']['suffix'] : '';
            $this->content['loggedin']['suffix'] = $loggedInSuffix . ' ' . $bellIcon;
        } else {
            // Modern themes: output icon normally into header
            $this->output($bellIcon);
        }
    }

    public function nav_user_search()
    {
        // Prepend the bell icon to the user navigation
        $this->fns_bell_icon();

        // Continue rendering the original user nav
        qa_html_theme_base::nav_user_search();
    }

    /**
     * @return void
     */
    private function addJsBodyFooter(): void
    {
        if (!isset($this->content['body_footer'])) {
            $this->content['body_footer'] = '';
        }

        $content = file_get_contents(QA_HTML_THEME_LAYER_DIRECTORY . 'public/SnowFlat/ui.min.js');
        $this->content['body_footer'] .= sprintf('<script>%s</script>', $content);
    }
}
