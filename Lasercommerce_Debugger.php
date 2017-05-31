<?php

include_once('Lasercommerce_OptionsManager.php');

class Lasercommerce_Debugger extends Lasercommerce_OptionsManager {

    public $defaultContext = array('source'=>'LaserCommerce', 'do_trace'=>true);
    static $logger;

    public static function init() {
        add_action('plugins_loaded', function(){
            self::$logger = wc_get_logger();
        });
    }

    public static function printFn($function){
        $response = (is_array($function)?get_class($function[0]).':'.$function[1]:$function);
        if(is_object($response) && ($response instanceof Closure)){
            $response="closure";
        }
        return $response;
    }

    public function patchAction($hookName, $oldFn, $newFn, $priority, $nargs){
        if( empty( $hookName ) ) return;

        $context = array_merge($this->defaultContext, array(
            'caller' => "ACT_".strtoupper($hookName),
        ));

        add_action(
            $hookName,
            function() use ($context, $hookName, $oldFn, $newFn, $priority, $nargs) {
                global $wp_action;

                if(LASERCOMMERCE_DEBUG) $this->procedureStart("FIRST ACTION", $context);

                $replace = false;
                if( isset($wp_action[$hookName]) ){
                    foreach( $wp_filter[$hookName] as $hook_priority => $hooks){
                        if ($hook_priority <> $priority ) continue;
                        foreach ($hooks as $hook_k => $hook_v) {
                            // $hook_echo = "hooked function: " . Lasercommerce_Debugger::printFn($hook_v['function']);
                            // $hook_echo .= " searching for function: " . Lasercommerce_Debugger::printFn($oldFn);
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
                            Lasercommerce_Debugger::printFn($hook_v['function']),
                            $priority,
                            Lasercommerce_Debugger::printFn($newFn)
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
                            // $hook_echo = "hooked function: " . Lasercommerce_Debugger::printFn($hook_v['function']);
                            // $hook_echo .= " searching for function: " . Lasercommerce_Debugger::printFn($oldFn);
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
                                Lasercommerce_Debugger::printFn($hook_v['function']),
                                $priority,
                                Lasercommerce_Debugger::printFn($newFn)
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
                global $wp_action;

                if(LASERCOMMERCE_DEBUG) $this->procedureStart("FIRST ACTION", $context);

                if( isset($wp_action[$hookName]) ){
                    foreach( $wp_action[$hookName] as $priority => $hooks){
                        foreach ($hooks as $hook_k => $hook_v) {
                            if ($priority < 0 || $priority >= 99999) continue;
                            $hook_echo = Lasercommerce_Debugger::printFn($hook_v['function']);
                            $this->procedureDebug("HOOKED (".serialize($priority)."): ".serialize($hook_k)."".serialize($hook_echo), $context);
                        }
                    }
                } else {
                    // $this->procedureDebug("NO HOOKS", $context);
                }
            },
            -1,
            0
        );
        add_action(
            $hookName,
            function() use ($context) {
                if(LASERCOMMERCE_DEBUG) $this->procedureDebug("FINAL ACTION", $context);
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
                    $this->procedureStart("", $context);
                }

                if( isset($wp_filter[$hookName]) ){
                    foreach( $wp_filter[$hookName] as $priority => $hooks){
                        foreach ($hooks as $hook_k => $hook_v) {
                            if ($priority < 0 || $priority >= 99999) continue;
                            $hook_echo=Lasercommerce_Debugger::printFn($hook_v['function']);
                            $this->procedureDebug("HOOKED (".serialize($priority)."): ".serialize($hook_k)."".serialize($hook_echo), $context);
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
