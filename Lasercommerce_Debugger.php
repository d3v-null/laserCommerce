<?php

/*

LaserCommerce Copyright (c) 2014, Derwent Laserphile
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:
    * Permission is granted to do so by the copyright holder, Laserphile
    * Redistributions of source code must retain the above copyright
      notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright
      notice, this list of conditions and the following disclaimer in the
      documentation and/or other materials provided with the distribution.
    * Neither the name of the <organization> nor the
      names of its contributors may be used to endorse or promote products
      derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR CPPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

include_once('Lasercommerce_OptionsManager.php');

class Lasercommerce_Debugger extends Lasercommerce_OptionsManager {
    private $_class = "LC_DP_";

    public $defaultContext = array('source'=>'LaserCommerce', 'do_trace'=>true);
    static $logger;

    public static function init() {
        add_action('plugins_loaded', function(){
            self::$logger = wc_get_logger();
        });
    }

    public function printFn($function){
        $context = array_merge($this->defaultContext, array(
            'caller' => $this->_class."PRINTFN",
        ));

        if(is_string($function)){
            if(LASERCOMMERCE_DEBUG) $this->procedureDebug("printFn called with string: $function", $context);
            return $function;
        }

        if(is_array($function) && !empty($function)){
            $response_components = array();
            if(isset($function[0])){
                $response_components[] = is_string($function[0])?$function[0]:get_class($function[0]);
            }
            if(isset($function[1])){
                $response_components[] = is_string($function[1])?$function[1]:"?";
            }
            return implode(":", $response_components);
        }

        if(is_object($function) && ($function instanceof Closure)){
            return "closure";
        }

        return serialize($function);
    }

    public function patchAction($hookName, $oldFn, $newFn, $priority, $nargs){
        if( empty( $hookName ) ) return;

        $context = array_merge($this->defaultContext, array(
            'caller' => "ACT_".strtoupper($hookName),
        ));

        add_action(
            $hookName,
            function() use ($context, $hookName, $oldFn, $newFn, $priority, $nargs) {
                global $wp_filter;

                if(LASERCOMMERCE_DEBUG) $this->procedureStart("FIRST ACTION", $context);

                $replace = false;
                if( isset($wp_filter[$hookName]) ){
                    foreach( $wp_filter[$hookName] as $hook_priority => $hooks){
                        if ($hook_priority <> $priority ) continue;
                        foreach ($hooks as $hook_k => $hook_v) {
                            // $hook_echo = "hooked function: " . $this->printFn($hook_v['function']);
                            // $hook_echo .= " searching for function: " . $this->printFn($oldFn);
                            // $hook_echo .= " equality: " . serialize($hook_v['function'] == $oldFn);
                            // $this->procedureDebug("HOOKED (".serialize($hook_priority)."): ".serialize($hook_k)."".serialize($hook_echo), $context);
                            if( $hook_v['function'] == $oldFn && $hook_priority == $priority ){
                                $replace = true;
                                break;
                            }
                        }
                    }
                    if($replace){
                        remove_action( $hookName, $oldFn, $priority, $nargs );
                        add_action( $hookName, $newFn, $priority, $nargs );
                        if(LASERCOMMERCE_DEBUG) $this->procedureDebug(sprintf(
                            "replacing function %s at %s with %s",
                            $this->printFn($hook_v['function']),
                            $priority,
                            $this->printFn($newFn)
                        ), $context);
                        break;
                    }
                }
            },
            -1,
            0
        );

        // TODO: This
    }

    public function patchFilter($hookName, $oldFn, $newFn, $priority, $nargs){
        if( empty( $hookName ) ) return;

        $context = array_merge($this->defaultContext, array(
            'caller'=>"FLT_".strtoupper($hookName),
        ));

        add_filter(
            $hookName,
            function($param) use ($context, $hookName, $oldFn, $newFn, $priority, $nargs) {
                global $wp_filter;

                try {
                    $context['args'] = "\$param=".serialize($param);
                } catch( Exception $e ){
                    $context['args'] = "\$param=<unserializable>";
                }
                if(LASERCOMMERCE_DEBUG) $this->procedureStart("", $context);

                $replace = false;
                if( isset($wp_filter[$hookName]) ){
                    foreach( $wp_filter[$hookName] as $hook_priority => $hooks){
                        if ($hook_priority <> $priority ) continue;
                        foreach ($hooks as $hook_k => $hook_v) {
                            // $hook_echo = "hooked function: " . $this->printFn($hook_v['function']);
                            // $hook_echo .= " searching for function: " . $this->printFn($oldFn);
                            // $hook_echo .= " equality: " . serialize($hook_v['function'] == $oldFn);
                            if( $hook_v['function'] == $oldFn && $hook_priority == $priority ){
                                $replace = true;
                                break;
                            }
                            // if(LASERCOMMERCE_DEBUG) $this->procedureDebug("HOOKED (".serialize($hook_priority)."): ".serialize($hook_k)."".serialize($hook_echo), $context);
                        }
                        if($replace){
                            remove_filter( $hookName, $oldFn, $priority, $nargs );
                            add_filter( $hookName, $newFn, $priority, $nargs );
                            if(LASERCOMMERCE_DEBUG) $this->procedureDebug(sprintf(
                                "replacing function %s at %s with %s",
                                $this->printFn($hook_v['function']),
                                $priority,
                                $this->printFn($newFn)
                            ), $context);
                            break;
                        }
                    }
                } else {
                    // $this->procedureDebug("NO HOOKS", $context);
                }

                return $param;
            },
            -1,
            1
        );
    }


    public function traceAction($hookName){
        if( empty( $hookName ) ) return;

        $context = array_merge($this->defaultContext, array(
            'caller'=>"ACT_".strtoupper($hookName),
        ));

        add_action(
            $hookName,
            function() use ($context, $hookName) {
                global $wp_filter;

                $this->procedureStart("FIRST ACTION", $context);

                if( isset($wp_filter[$hookName]) ){
                    foreach( $wp_filter[$hookName] as $priority => $hooks){
                        if ($priority < 0 || $priority >= 99999) continue;
                        foreach ($hooks as $hook_k => $hook_v) {
                            $hook_echo = $this->printFn($hook_v['function']);
                            $this->procedureDebug("HOOKED (".serialize($priority)."): ".serialize($hook_k)."".serialize($hook_echo), $context);
                        }
                    }
                } else {
                    $this->procedureDebug("NO ACTIONS: $hookName; ".serialize(array_keys($wp_filter)), $context);
                }
            },
            -1,
            0
        );
        add_action(
            $hookName,
            function() use ($context) {
                $this->procedureDebug("FINAL ACTION", $context);
            },
            99999,
            0
        );
    }

    public function traceFilter($hookName){
        if( empty( $hookName ) ) return;

        $context = array_merge($this->defaultContext, array(
            'caller'=>"FLT_".strtoupper($hookName),
        ));

        // get list of things hooked to this filter
        add_filter(
            $hookName,
            function($param) use ($context, $hookName) {
                global $wp_filter;

                if(LASERCOMMERCE_DEBUG) {
                    try {
                        $context['args'] = "\$param=".serialize($param);
                    } catch( Exception $e ){
                        $context['args'] = "\$param=<unserializable>";
                    }
                    $this->procedureStart("FIRST FILTER", $context);
                }

                if( isset($wp_filter[$hookName]) ){
                    foreach( $wp_filter[$hookName] as $priority => $hooks){
                        if ($priority < 0 || $priority >= 99999) continue;
                        foreach ($hooks as $hook_k => $hook_v) {
                            $hook_echo=$this->printFn($hook_v['function']);
                            $this->procedureDebug("HOOKED (".serialize($priority)."): ".serialize($hook_k)."".serialize($hook_echo), $context);
                        }
                    }
                } else {
                    $this->procedureDebug("NO FILTERS: $hookName; ".serialize(($wp_filter)), $context);
                }

                return $param;
            },
            -1,
            1
        );
        add_filter(
            $hookName,
            function($param) use ($context) {

                if(LASERCOMMERCE_DEBUG) {
                    try {
                        $this->procedureDebug("FINAL FILTER: ".serialize($param), $context);
                    } catch( Exception $e ){
                        $this->procedureDebug("FINAL FILTER: <unserializable>", $context);
                    }
                }
                return $param;
            },
            99999,
            1
        );
    }


    /**
    * Add a log entry.
    *
    * @param string $level One of the following:
    *     'emergency': System is unusable.
    *     'alert': Action must be taken immediately.
    *     'critical': Critical conditions.
    *     'error': Error conditions.
    *     'warning': Warning conditions.
    *     'notice': Normal but significant condition.
    *     'informational': Informational messages.
    *     'debug': Debug-level messages.
    * @param string $message Log message.
    * @param array $context Optional. Additional information for log handlers.
    */
    public function log($level, $message, array $context=array()){
        $context = array_merge($this->defaultContext, $context);

        $messageComponents = array(
            // serialize($context),
            $message
        );
        if( isset($context['caller']) ){
            array_unshift($messageComponents, /*"\n".*/"caller: ".$context['caller']);
        }
        if( isset($context['trace']) ){
            array_unshift($messageComponents, /*"\n".*/"trace: ".$context['trace']);
        }
        if( isset($context['debugdebug']) && ! empty($context['debugdebug']) ){
            array_push($messageComponents, "\n"."debugdebug: ".$context['debugdebug']);
        }

        $message = implode("; ", $messageComponents);
        if(!empty(self::$logger)){
            self::$logger->log($level, $message, $context);
        } else {
            error_log($message);
        }
    }

    public function debug($message, array $context=array()){
        $this->log('debug', $message, $context);
    }

    public function procedureStart($message='', $context=array()){
        $message = 'BEGIN: ' . $message;
        if( isset($context['args']) ){
            $message = "(" . $context['args'] . ") " . $message;
        }
        $this->procedureDebug($message, $context, 1);
    }

    public function procedureEnd($message='', $context=array()){
        if( isset($context['return']) ){
            $message = "returned: ". $context['return'] . " " . $message;
        }
        $message = 'END: ' . $message;
        $this->procedureDebug($message, $context, 1);
    }

    public function procedureDebug($message, $context=array(), $skipFrames=0){
        $skipFrames ++;
        $context['debugdebug'] = '';
        if( isset($context['do_trace']) and $context['do_trace']){
            $trace_frames = wp_debug_backtrace_summary(array(), $skipFrames, false);
            $ignore_frames = array(
                "require_once('wp-load.php')",
                "require_once('wp-config.php')",
                "require_once('wp-settings.php')",
                "do_action('wp_loaded')",
                "WP_Hook->do_action",
                "WP_Hook->apply_filters",
                "call_user_func_array",
                "WP_List_Table->display",
                "WP_List_Table->display_rows_or_placeholder",
                "WP_Posts_List_Table->display_rows",
                "WP_Posts_List_Table->_display_rows",
                "WP_Posts_List_Table->single_row",
                "WP_List_Table->single_row_columns",
                "WP_Posts_List_Table->column_default",
                "do_action('manage_product_posts_custom_column')"
            );
            $trace_frames = array_diff($trace_frames, $ignore_frames);
            foreach($trace_frames as &$frame){
                $before = $frame;
                $after = preg_replace("/lasercommerce/i", "LC", $frame);
                if($before != $after){
                    $frame = $after;
                    // $context['debugdebug'] .= "b:".serialize($before).";a:".serialize($after);
                }
            }
            $context['trace'] = implode("|", array_reverse($trace_frames));
        }
        if( !isset($context['caller']) ){
            $backtrace = wp_debug_backtrace_summary(array(), $skipFrames, false);
            $context['caller'] = array_pop($backtrace);
        }
        $this->debug($message, $context);
    }
}

?>
