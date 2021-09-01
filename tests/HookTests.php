<?php
use Splitice\EventTrait\THookable;

trait TestTrait3 {
    public $TestTrait3Init = 0;

    protected function __TestTrait3__init(){
        $this->TestTrait3Init++;
    }
}

trait TestTrait1 {
    use TestTrait3;
    public $TestTrait1Init = 0;

    protected function __TestTrait1__init(){
        $this->TestTrait1Init++;
    }
}

trait TestTrait2 {
    public $TestTrait2Init = 0;

    protected function __TestTrait2__init(){
        $this->TestTrait2Init++;
    }
}

class TestObjectParent {
    use TestTrait2;
}

class TestObjectChild extends TestObjectParent {
    use THookable;
    use TestTrait1;

    function __construct(){
        $this->hookInit();
    }

    function call_action1(){
        $this->call_action('action1',func_get_args());
    }

    function call_filter1($value){
        $this->call_filter('filter1', $value);
        return $value;
    }
}

class HookTests extends PHPUnit_Framework_TestCase {
    function testSimpleAction(){
        $success = false;
        $func = function() use(&$success){
            $success = true;
        };
        $obj = new TestObjectChild();
        $obj->register_action('action1', $func);
        $obj->call_action1();
        $this->assertEquals(true, $success);
    }

    function testSimpleActionMultiple(){
        $success = 0;
        $func = function() use(&$success){
            $success++;
        };
        $obj = new TestObjectChild();
        $obj->register_action('action1', $func);
        $obj->call_action1();
        $obj->call_action1();
        $this->assertEquals(2, $success);
    }

    function testSimpleActionArguments(){
        $success = 0;
        $func = function($a) use(&$success){
            $success += $a[0];
        };
        $obj = new TestObjectChild();
        $obj->register_action('action1', $func);
        $obj->call_action1(1);
        $obj->call_action1(2);
        $this->assertEquals(3, $success);
    }

    function testFilterArguments(){
        $func = function(&$a){
            $a = 1;
        };
        $obj = new TestObjectChild();
        $obj->register_filter('filter1', $func);
        $result = $obj->call_filter1(0);
        $this->assertEquals(1, $result);
    }

    function testTraitInitLv1(){
        $obj = new TestObjectChild();
        $this->assertEquals(1, $obj->TestTrait1Init);
    }

    function testTraitInitLv2(){
        $obj = new TestObjectChild();
        $this->assertEquals(1, $obj->TestTrait2Init);
    }

    function testTraitInitLv3(){
        $obj = new TestObjectChild();
        $this->assertEquals(1, $obj->TestTrait3Init);
    }
} 