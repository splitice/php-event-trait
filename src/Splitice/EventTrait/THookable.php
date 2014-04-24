<?php
namespace Splitice\EventTrait;

use X4B\DB\Port;

trait THookable {
    private $actions = array();

    private static $init_cache = array();

    protected function hookInit(){
        $class = get_called_class();
        if(isset(self::$init_cache[$class])){
            $functor = self::$init_cache[$class];
            if($functor !== null)
                $functor($this);
            return;
        }

        $t = $this;
        $functor = null;
        $refclass = new \ReflectionObject($this);
        foreach($refclass->getTraits() as $trait){
            $method = '__'.$trait->getShortName().'__init';
            if(method_exists($t,$method)){
                if($functor === null){
                    $functor = function($t) use($method){
                        $t->$method();
                    };
                }else{
                    $functor_new = function($t) use($method, $functor){
                        $t->$method();
                        $functor($t);
                    };
                    $functor = $functor_new;
                }
            }
        }

        self::$init_cache[$class] = $functor;
        if($functor !== null)
            $functor($this);
    }

    function register_action($action, $functor){
        if(isset($this->actions[$action])){
            $tocall = $functor;
            $chain = $this->actions[$action];
            //echo var_dump($action, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
            $functor = function($arg) use($tocall,$chain){
                $tocall($arg);
                $chain($arg);
            };
        }
        $this->actions[$action] = $functor;
    }

    function register_filter($action, $functor){

    }

    protected function call_action($action, $arg = null){
        if(isset($this->actions[$action])){
            $this->actions[$action]($arg);
        }
    }
}