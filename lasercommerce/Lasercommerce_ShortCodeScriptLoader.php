<?php
/*
    "WordPress Plugin Template" Copyright (C) 2014 Michael Simpson  (email : michael.d.simpson@gmail.com)

    This file is part of WordPress Plugin Template for WordPress.

    Copyright (c) 2014, Michael Simpson
    All rights reserved.

    Redistribution and use in source and binary forms, with or without modification,
    are permitted provided that the following conditions are met:

    - Redistributions of source code must retain the above copyright notice, this list of conditions and the
      following disclaimer.

    - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following
      disclaimer in the documentation and/or other materials provided with the distribution.

    - Neither the name of Michael Simpson nor the names of its contributors may be used to endorse or promote products
      derived from this software without specific prior written permission.

    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
    DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
    SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
    SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
    WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
    OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

include_once('Lasercommerce_ShortCodeLoader.php');

/**
 * Adapted from this excellent article:
 * http://scribu.net/wordpress/optimal-script-loading.html
 *
 * The idea is you have a shortcode that needs a script loaded, but you only
 * want to load it if the shortcode is actually called.
 */
abstract class Lasercommerce_ShortCodeScriptLoader extends Lasercommerce_ShortCodeLoader {

    var $doAddScript;

    public function register($shortcodeName) {
        $this->registerShortcodeToFunction($shortcodeName, 'handleShortcodeWrapper');

        // It will be too late to enqueue the script in the header,
        // but can add them to the footer
        add_action('wp_footer', array($this, 'addScriptWrapper'));
    }

    public function handleShortcodeWrapper($atts) {
        // Flag that we need to add the script
        $this->doAddScript = true;
        return $this->handleShortcode($atts);
    }


    public function addScriptWrapper() {
        // Only add the script if the shortcode was actually called
        if ($this->doAddScript) {
            $this->addScript();
        }
    }

    /**
     * @abstract override this function with calls to insert scripts needed by your shortcode in the footer
     * Example:
     *   wp_register_script('my-script', plugins_url('js/my-script.js', __FILE__), array('jquery'), '1.0', true);
     *   wp_print_scripts('my-script');
     * @return void
     */
    public abstract function addScript();

}
