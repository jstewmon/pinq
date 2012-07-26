<?

class DistinctIterator extends FilterIterator {
  private $store;
  
  public function __construct($iterator) {
    if(!($iterator instanceof Iterator)) {
      $iterator = new ArrayIterator($iterator);
    }
    $this->store = new SplObjectStorage();
    parent::__construct($iterator);
  }

  public function accept() {
    $current = $this->current();
    if($this->store->offsetExists($current)) {
      return false;
    }
    else {
      $this->store[$current] = $current;
      return true;
    }
  }

}

class ExceptIterator extends FilterIterator {
  private $exceptStore;

  public function __construct($iterator, $except) {

    foreach($except as $value) {
      $this->exceptStore->detach($value);
    }
    parent::__construct($iterator);
  }

  public function accept() {

    if($this->exceptStore->offsetExists($current)) {
      return false;
    }
    else return true;
  }
}

class PinqIterator extends FilterIterator {

  public $filter;

  public function __construct($iterator, $filter) {
    if(!is_callable($filter)) {
      throw new InvalidArgumentException('$filter should be callable');
    }
    parent::__construct($iterator);
    $this->filter = $filter;
  }

  public function accept() {
    $iterator = $this->getInnerIterator();
    $key = $iterator->key();
    $current = $iterator->current();
    return $this->filter($key, $current, $iterator);
  }
}


/*
   php provides several iterators already.
   
   FilterIterator, AppendIterator, LimitIterator and MultipleIterator (zip) are probably
   handy for implementing most of the deferred execution methods. We just need to define
   an implemetation for them that allows us to pass our callbacks.
   
   maybe we can use IteratorIterator to wrap all the iterators we want to add from our
   deferred execution methods?
*/
class PinqList implements IteratorAggregate {

  public $iterator;

  function __construct($iterator = array()) {
    if($iterator instanceof Iterator) {
      $this->iterator = $iterator;
    }
    else $this->iterator = new ArrayIterator($iterator);
  }

  public static function create($array = array()) {
    return new PinqList($array);
  }

  public function aggregate($seed = null, $callback) {
    return Pinq::aggregate($this, $seed, $callback);
  }

  public function all($callback) {
    return Pinq::all($this, $callback);
  }

  public function any($callback) {
    return Pinq::any($this, $callback);
  }

  public function average($projection = null) {
    return Pinq::average($this, $projection);
  }

  // deferred
  public function concat($iterator) {
    $itr = new AppendIterator();
    $itr->append($this->iterator);
    $itr->append($iterator);
    $this->iterator = $itr;
    return $this;
  }

  public function contains($item) {
    return Pinq::contains($this, $item);
  }

  public function containsKey($key) {
    return Pinq::containsKey($this, $key);
  }

  public function count() {
    return Pinq::count($this);
  }

  // deferred
  public function distinct() {
    $this->iterator = new DistinctIterator($this);
  }

  // deferred
  public function except($array) {
    $this->iterator = new ExceptIterator($this, $array);
  }

  public function first($callback = null) {
    return Pinq::first($this, $callback);
  }

  // deferred
  public function groupBy($keySelector, $valueSelector = null) {

  }

  // deferred
  public function groupJoin() {

  }

  // deferred
  public function intersct($array) {

  }

  // deferred
  public function join() {

  }

  public function last($callback = null) {
    return Pinq::last($this, $callback);
  }

  public function max($projection = null) {

  }

  public function min($projection = null) {

  }

  // deferred
  public function reverse() {

  }

  // deferred
  public function select($callback) {
    return stackCallback($callback);
  }

  // deferred
  public function selectMany() {

  }

  // deferred
  public function skip($howMany) {

  }

  // deferred
  public function skipWhile($filter) {

  }

  public function sum($projection = null) {

  }

  // deferred
  public function take($howMany) {

  }

  // deferred
  public function takeWhile($filter) {
    
  }

  // deferred
  public function union($array) {

  }

  // deferred
  public function where($callback) {
    return stackCallback($callback);
  }

  // deferred
  public function zip($array, $callback) {

  }

  public function getIterator() {
    return $this->iterator;
  }

  // private function stackCallback($callback) {
  //   $self = $this;
  //   $this->callback = function($key, $value, $iterator, $cb) use($self, $callback) {
  //     // return $callback($self->callback($key, $value, $iterator));
  //     return $self->callback($key, $value, $iterator, $callback);
  //   };
  //   return $this;
  // }

  // /* Iterator implementation */
  // public function current() {
  //   $key = parent::key();
  //   $value = parent::current();
  //   return $this->callback($key, $value, $this)->value;
  // }

  // public function key() {
  //   $key = parent::key();
  //   $value = parent::current();
  //   return $this->callback($key, $value, $this)->key;
  // }

  // public function next() {
  //   while(!($var = $this->callback()) && valid($this->array)) {
  //     return $var;
  //   }
  // }
  // public function rewind() {

  // }
  // public function valid() {

  // }
}

/*
 * $callback is aways callable($key, $value)
 *
 */
class Pinq {

  public static function all($iterator, $callback) {
    foreach($iterator as $key => $value) {
      if(!callback($key, $value)) {
        return false;
      }
    }
    return true;
  }
  
  public static function any($iterator, $callback = false) {
    if(!$callback) return !empty($iterator);
    foreach($iterator as $key => $value) {
      if($callback($key, $value)) return true;
    }
    return false;
  }

  public static function aggregate($iterator, $seed, $callback) {
    foreach($iterator as $key => $value) {
      $seed = $callback($seed, $key, $value);
    }
    return $seed;
  }

  public static function average($iterator, $callback = false) {
    $avg = 0;
    $len = count($iterator);
    foreach($iterator as $key => $value) {
      if($callback) {
        $value = $callback($key, $value, $iterator);
      }
      $avg += $value / $len;
    }
    return $avg;
  }

  public static function contains($iterator, $item) {
    foreach($iterator as $value) {
      if($item == $value) return true;
    }
    return false;
  }

  public static function count($iterator) {
    return count($iterator);
  }

  public static function containsKey($iterator, $key) {
    return array_key_exists($key, $iterator);
  }

  public static function first($iterator, $callback = null) {
    self::ensureCallback($callback, true);

    foreach($iterator as $key => $value) {
      if(!$callback) return array($key, $value, 'key' => $key, 'value' => $value);
      if($callback($key, $value, $iterator)) {
        return array($key, $value, 'key' => $key, 'value' => $value);
      }
    }
    return null;
  }

  public static function last($iterator, $callback = null) {
    self::ensureCallback($callback, true);

    $array = array();
    if(!is_array($iterator)) {
      foreach($iterator as $key => $value) {
        $array[$key] = $value;
      }
    }
    else $array = $iterator;
    return self::lastFromArray($array, $callback);
  }

  private static function lastFromArray($iterator, $callback = null) {
    self::ensureCallback($callback, true);
    
    $value = end($iterator);
    $key = key($iterator);
    do {
      if($callback == null) {
        return array($key, $value, 'key' => $key, 'value' => $value);
      }
      if($callback($key, $value, $iterator)) {
        return array($key, $value, 'key' => $key, 'value' => $value);
      }
    }
    while($value = prev($iterator)); 
  }

  public static function where($iterator, $callback) {
    $where = array();
    foreach($iterator as $key => $value) {
      if($callback($key, $value, $iterator)) {
        $where[$key] = $value;
      }
    }
    return $where;
  }

  public static function repeat($value, $times) {

  }

  public static function ensureIterator($iterator) {
    if($iterator instanceof Traversable) return;
    if(is_array($iterator)) return;
    throw new InvalidArgumentException('$iterator must be either an array or an instance of Traversable');
  }

  public static function ensureCallback($callback, $allowNull = false) {
    if($callback == null && $allowNull) {
      return;
    }
    if(!is_callable($callback)) {
      throw new InvalidArgumentException('$callback must be callable');
    }
  }
}