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

abstract class Lasercommerce_ShortCodeLoader {

    /**
     * @param  $shortcodeName mixed either string name of the shortcode
     * (as it would appear in a post, e.g. [shortcodeName])
     * or an array of such names in case you want to have more than one name
     * for the same shortcode
     * @return void
     */
    public function register($shortcodeName) {
        $this->registerShortcodeToFunction($shortcodeName, 'handleShortcode');
    }

    /**
     * @param  $shortcodeName mixed either string name of the shortcode
     * (as it would appear in a post, e.g. [shortcodeName])
     * or an array of such names in case you want to have more than one name
     * for the same shortcode
     * @param  $functionName string name of public function in this class to call as the
     * shortcode handler
     * @return void
     */
    protected function registerShortcodeToFunction($shortcodeName, $functionName) {
        if (is_array($shortcodeName)) {
            foreach ($shortcodeName as $aName) {
                add_shortcode($aName, array($this, $functionName));
            }
        }
        else {
            add_shortcode($shortcodeName, array($this, $functionName));
        }
    }

    /**
     * @abstract Override this function and add actual shortcode handling here
     * @param  $atts shortcode inputs
     * @return string shortcode content
     */
    public abstract function handleShortcode($atts);

}
