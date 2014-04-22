<?php
namespace Splitice\EventTrait;

trait THookable {
    private $actions = array();

    private static $init_cache = array();

    protected function hookInit(){
        $class = get_called_class();
        if(isset(self::$init_cache[$class])){
            $functor = self::$init_cache[$class];
            if($functor !== null)
                $functor();
            return;
        }

        $t = $this;
        $functor = null;
        $refclass = new \ReflectionObject($this);
        foreach($refclass->getTraits() as $trait){
            $method = '__'.$trait->getShortName().'__init';
            if(method_exists($t,$method)){
                if($functor === null){
                    $functor = function() use($t,$method){
                        $t->$method();
                    };
                }else{
                    $functor = function() use($t,$method){
                        $t->$method();
                        $functor();
                    };
                }
            }
        }

        self::$init_cache[$class] = $functor;
        if($functor !== null)
            $functor();
    }

    function register_action($action, $functor){
        if(isset($this->actions[$action])){
            $tocall = $functor;
            $chain = $this->actions[$action];

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