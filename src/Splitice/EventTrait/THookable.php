<?php
namespace Splitice\EventTrait;

trait THookable {
    /**
     * @var array
     */
    private $actions = array();

    private static $init_cache = array();

    /**
     * Call this from the constructor of the class being hooked
     */
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

    /**
     * Register a $functor to be performed on a specific $action name
     *
     * @param $action
     * @param callable $functor
     */
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

    /**
     * Call a $action with $arg
     *
     * @param $action
     * @param null $arg
     */
    protected function call_action($action, $arg = null){
        if(isset($this->actions[$action])){
            $this->actions[$action]($arg);
        }
    }
}