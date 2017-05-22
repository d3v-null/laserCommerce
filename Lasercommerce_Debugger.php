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
            $context['caller'] = array_pop(wp_debug_backtrace_summary(array(), $skipFrames, false));
        }
        $this->debug($message, $context);
    }
}

?>
