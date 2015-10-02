<?php
namespace Splitice\EventTrait;

/**
 * A trait to add hooks to a class / object.
 *
 * @package Splitice\EventTrait
 */
trait THookable {
    /**
     * @var callable[] all actions registered on this object
     */
    private $actions = array();

    /**
     * @var callable[] all filters registered on this object
     */
    private $filters = array();

    /**
     * Cache the init functors, creating them is expensive as it requires
     * recursive reflection on each object construction.
     *
     * @var callable[] init functors
     */
    private static $init_cache = array();

    /**
     * Call this from the constructor of the class being hooked
     */
    protected function hookInit(){
        $class = get_called_class();

        if(isset(self::$init_cache[$class])){
            $functor = self::$init_cache[$class];
        }else {
            $refclass = new \ReflectionObject($this);
            $functor = $this->get_init_functor($refclass);

            //cache the functor
            if($functor === null){
                $functor = true;
            }

            self::$init_cache[$class] = $functor;
        }

        //execute the functor
        if($functor !== null && $functor !== true)
            $functor($this);
    }

    /**
     * Push a method onto the functor chain
     *
     * @param string $method
     * @param callable|null $functor
     * @return callable
     */
    private static function functor_chain($method, $functor){
        if($functor === null){
            $functor = function($t) use($method){
                $t->$method();
            };
        }else{
            $functor_new = function($t) use($method, $functor){
                $t->$method();
                /** @var callable $functor */
                $functor($t);
            };
            $functor = $functor_new;
        }

        return $functor;
    }

    /**
     * Get a functor for initialization from a ReflectionObject.
     *
     * This function is recursively called for all traits, and traits of parents etc.
     *
     * @param \Reflector $refclass
     * @param callable|null $functor
     * @param array $added
     * @return callable|null
     */
    private function get_init_functor(\Reflector $refclass, $functor = null, &$added = array()){
        //Blame PHP for not having a standard reflection interface
        //This can be a reflectionobject, reflectionclass or reflectiontrait
        /** @var \ReflectionClass $refclass */
        $parentClass = $refclass->getParentClass();
        if($parentClass){
            $functor = $this->get_init_functor($parentClass, $functor, $added);
        }

        foreach($refclass->getTraits() as $trait){
            $functor = $this->get_init_functor($trait, $functor, $added);
            $short_name = $trait->getShortName();

            //Only init once
            if(isset($added[$short_name])){
                continue;
            }
            $added[$short_name] = true;

            $method = '__'.$short_name.'__init';
            if(method_exists($this,$method)){
                $functor = self::functor_chain($method, $functor);
            }
        }

        return $functor;
    }

    /**
     * Register a $functor to be performed on a specific $action name.
     *
     * Actions are executed from last registered, to first registered.
     *
     * @param string $action
     * @param callable $functor
     */
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

    /**
     * Registers a filter to be called on $action events.
     *
     * A filter is a function whose arguments are passed by reference.
     * If the filter returns true, processing of the filter chain is aborted.
     * Filters like actions are processed from last registered, to first.
     *
     * @param string $filter
     * @param callable $functor
     * @returns bool true if the chain was aborted
     */
    function register_filter($filter, $functor){
        if(isset($this->filters[$filter])){
            $tocall = $functor;
            if(is_array($tocall) && count($tocall) == 2){
                $a = $functor[0];
                $b = $functor[1];
                if(is_object($tocall[0])){
                    $tocall = function(&$arg) use ($a,$b){
                        return $a->$b($arg);
                    };
                }else{
                    $tocall = function(&$arg) use ($a,$b){
                        return $a::$b($arg);
                    };
                }
            }
            $chain = $this->filters[$filter];
            $functor = function(&$arg) use($tocall,$chain){
                if($tocall($arg) || $chain($arg)){
                    return true;
                }
            };
        }
        $this->filters[$filter] = $functor;
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

    /**
     * Call a $filter with $arg
     *
     * @param $filter
     * @param null $arg
     */
    protected function call_filter($filter, &$arg = null){
        if(isset($this->filters[$filter])){
            return $this->filters[$filter]($arg);
        }
    }
}