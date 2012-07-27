<?

class DistinctIterator extends FilterIterator
{
  private $store;
  
  public function __construct($iterator)
  {
    if(!($iterator instanceof Iterator)) {
      $iterator = new ArrayIterator($iterator);
    }
    $this->store = new SplObjectStorage();
    parent::__construct($iterator);
  }

  public function accept()
  {
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

class ExceptIterator extends FilterIterator
{
  private $exceptStore;

  public function __construct($iterator, $except)
  {
    foreach($except as $value) {
      $this->exceptStore->detach($value);
    }
    parent::__construct($iterator);
  }

  public function accept()
  {

    if($this->exceptStore->offsetExists($current)) {
      return false;
    }
    else return true;
  }
}

class WhereIterator extends FilterIterator
{

  public $filter;

  public function __construct($iterator, $filter)
  {
    if(!is_callable($filter)) {
      throw new InvalidArgumentException('$filter should be callable');
    }
    parent::__construct($iterator);
    $this->filter = $filter;
  }

  public function accept()
  {
    $iterator = $this->getInnerIterator();
    $key = $iterator->key();
    $current = $iterator->current();
    return call_user_func($this->filter, $key, $current);
  }
}

class TakeWhileIterator extends WhereIterator
{
  public $active = true;

  public function __construct($iterator, $filter)
  {
    parent::__construct($iterator, $filter);
  }

  public function rewind()
  {
    $this->active = true;
    parent::rewind();
  }

  public function accept()
  {
    return $this->active = parent::accept();
  }

  public function valid()
  {
    return $this->active && parent::valid();
  }
}

class SkipWhileIterator extends WhereIterator
{
  public $active = false;

  public function __construct($iterator, $filter)
  {
    parent::__construct($iterator, $filter);
  }

  public function rewind()
  {
    $this->active = false;
    parent::rewind();
  }

  public function accept()
  {
    return $this->active || ($this->active = !parent::accept());
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
class PinqIterator implements OuterIterator
{

  public $iterator;

  function __construct($iterator = array())
  {
    if($iterator instanceof Iterator) {
      $this->iterator = $iterator;
    }
    else $this->iterator = new ArrayIterator($iterator);
  }

  public static function create($array = array())
  {
    return new PinqIterator($array);
  }

  public function aggregate($seed = null, $callback)
  {
    return Pinq::aggregate($this->iterator, $seed, $callback);
  }

  public function all($callback)
  {
    return Pinq::all($this, $callback);
  }

  public function any($callback)
  {
    return Pinq::any($this, $callback);
  }

  public function average($projection = null)
  {
    return Pinq::average($this, $projection);
  }

  // deferred
  public function concat($iterator)
  {
    $itr = new AppendIterator();
    $itr->append($this->iterator);
    $itr->append(Pinq::ensureIterator($iterator, true));
    $this->iterator = $itr;
    return $this;
  }

  public function contains($item)
  {
    return Pinq::contains($this, $item);
  }

  public function containsKey($key)
  {
    return Pinq::containsKey($this, $key);
  }

  public function count()
  {
    return Pinq::count($this->iterator);
  }

  // deferred
  public function distinct()
  {
    $this->iterator = new DistinctIterator($this->iterator);
    return $this;
  }

  // deferred
  public function except($array)
  {
    $this->iterator = new ExceptIterator($this->iterator, $array);
    return $this;
  }

  public function first($callback = null)
  {
    return Pinq::first($this->iterator, $callback);
  }

  // deferred
  public function groupBy($keySelector, $valueSelector = null)
  {

    return $this;
  }

  // deferred
  public function groupJoin()
  {

    return $this;
  }

  // deferred
  public function intersct($array)
  {

    return $this;
  }

  // deferred
  public function join()
  {
    return $this;
  }

  public function last($callback = null)
  {
    return Pinq::last($this->iterator, $callback);
  }

  public function limit($skip, $take) {
    $this->iterator = Pinq::limit($this->iterator, $skip, $take);
    return $this;
  }

  public function max($projection = null)
  {

  }

  public function min($projection = null)
  {

  }

  // deferred
  public function reverse()
  {
    return $this;
  }

  // deferred
  public function select($callback)
  {

    return $this;
  }

  // deferred
  public function selectMany()
  {

    return $this;
  }

  // deferred
  public function skip($howMany)
  {
    $this->iterator = Pinq::skip($this->iterator, $howMany);
    return $this;
  }

  // deferred
  public function skipWhile($filter)
  {
    $this->iterator = new SkipWhileIterator($this->iterator, $filter);
    return $this;
  }

  public function sum($projection = null)
  {
    return Pinq::sum($this->iterator, $projection);
  }

  // deferred
  public function take($howMany)
  {
    $this->iterator = Pinq::take($this->iterator, $howMany);
    return $this;
  }

  // deferred
  public function takeWhile($filter)
  {
   $this->iterator = new TakeWhileIterator($this->iterator, $filter); 
    return $this;
  }

  public function toArray($withKeys = false)
  {
    $array = array();
    foreach($this as $key => $value) {
      if($withKeys) $array[$key] = $value;
      else $array[] = $value;
    }
    return $array;
  }

  // deferred
  public function union($iterator)
  {
    $appender = new AppendIterator();
    $appender->append($this->iterator);
    $appender->append(Pinq::ensureIterator($iterator, true));
    $this->iterator = new DistinctIterator($appender->getInnerIterator());
    return $this;
  }

  // deferred
  public function where($callback)
  {
    $this->iterator = new WhereIterator($this->iterator, $callback);
    return $this;
  }

  // deferred
  public function zip($array, $callback)
  {

    return $this;
  }

  public function getInnerIterator()
  {
    return $this->iterator;
  }

  /* Iterator implementation */
  public function current()
  {

    return $this->iterator->current();
  }

  public function key()
  {
    return $this->iterator->key();
  }

  public function next()
  {
    return $this->iterator->next();
  }
  public function rewind()
  {
    return $this->iterator->rewind();
  }
  public function valid()
  {
    return $this->iterator->valid();
  }
}

/*
 * $callback is aways callable($key, $value)
 *
 */
class Pinq
{

  public static function all($iterator, $callback)
  {
    foreach($iterator as $key => $value) {
      if(!callback($key, $value)) {
        return false;
      }
    }
    return true;
  }
  
  public static function any($iterator, $callback = false)
  {
    if(!$callback) return !empty($iterator);
    foreach($iterator as $key => $value) {
      if($callback($key, $value)) return true;
    }
    return false;
  }

  public static function aggregate($iterator, $seed, $callback)
  {
    self::ensureIterator($iterator);
    self::ensureCallback($callback);

    foreach($iterator as $key => $value) {
      $seed = $callback($seed, $key, $value);
    }
    return $seed;
  }

  public static function average($iterator, $callback = false)
  {
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

  public static function contains($iterator, $item)
  {
    foreach($iterator as $value) {
      if($item == $value) return true;
    }
    return false;
  }

  public static function count($iterator)
  {
    return count($iterator);
  }

  public static function containsKey($iterator, $key)
  {
    return array_key_exists($key, $iterator);
  }

  public static function first($iterator, $callback = null)
  {
    self::ensureIterator($iterator);
    self::ensureCallback($callback, true);

    foreach($iterator as $key => $value) {
      if(!$callback) return array($key, $value, 'key' => $key, 'value' => $value);
      if($callback($key, $value, $iterator)) {
        return array($key, $value, 'key' => $key, 'value' => $value);
      }
    }
    return null;
  }

  public static function last($iterator, $callback = null)
  {
    self::ensureIterator($iterator);
    self::ensureCallback($callback, true);

    if($iterator instanceof SeekableIterator && $iterator instanceof Countable) {
      return self::lastFromSeekableCountable($iterator, $callback);
    }

    $array = array();
    if(!is_array($iterator)) {
      foreach($iterator as $key => $value) {
        $array[$key] = $value;
      }
    }
    else $array = $iterator;
    return self::lastFromArray($array, $callback);
  }

  private static function lastFromSeekableCountable($iterator, $callback)
  {
    $position = $iterator->count() - 1;
    do {
      $iterator->seek($position);
      $key = $iterator->key();
      $value = $iterator->current();
      if($callback == null) {
        return array($key, $value, 'key' => $key, 'value' => $value);
      }
      if($callback($key, $value)) {
        return array($key, $value, 'key' => $key, 'value' => $value);
      }
    }
    while ($position-- > 0);
  }

  private static function lastFromArray($iterator, $callback = null)
  { 
    $value = end($iterator);
    $key = key($iterator);
    do {
      if($callback == null) {
        return array($key, $value, 'key' => $key, 'value' => $value);
      }
      if($callback($key, $value)) {
        return array($key, $value, 'key' => $key, 'value' => $value);
      }
    }
    while($value = prev($iterator)); 
  }

  public static function limit($iterator, $skip, $take)
  {
    return new LimitIterator(self::ensureIterator($iterator, true), $skip, $take);
  }

  public static function skip($iterator, $howMany)
  {
    return new LimitIterator(self::ensureIterator($iterator, true), $howMany);
  }

  public static function sum($iterator, $projection = null) 
  {
    self::ensureIterator($iterator);
    self::ensureCallback($projection, true);
    return self::aggregate($iterator, 0, function($total, $key, $value) use($projection) {
      if($projection == null) {
        return $total + $value;
      }
      else return $total + $projection($key, $value);
    });
  }

  public static function take($iterator, $howMany)
  {
    return new LimitIterator(self::ensureIterator($iterator, true), 0, $howMany);
  }

  public static function where($iterator, $callback)
  {
    return PinqIterator::create($iterator)->where($callback);
  }

  public static function repeat($value, $times)
  {

  }

  public static function ensureIterator($iterator, $convertArray = false)
  {
    if($iterator instanceof Traversable) return $iterator;
    if(is_array($iterator))return $convertArray ? new ArrayIterator($iterator) : $iterator;
    throw new InvalidArgumentException('$iterator must be either an array or an instance of Traversable');
  }

  public static function ensureCallback($callback, $allowNull = false)
  {
    if($callback == null && $allowNull) {
      return;
    }
    if(!is_callable($callback)) {
      throw new InvalidArgumentException('$callback must be callable');
    }
  }
}