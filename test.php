<?

// $i = array(1,2,3);
// foreach ($i as $k => $v) {
//   var_dump($v);
//   echo 'Inner' . PHP_EOL;
//   foreach ($i as $k2 => $v2) {
//     var_dump($v2);
//   }
// }

echo count(new ArrayIterator(array(1,2,3))) . PHP_EOL;

echo is_array(new ArrayIterator()) . PHP_EOL;
echo is_a(new ArrayIterator(), 'ArrayAccess') . PHP_EOL;
echo array_key_exists('key', new ArrayIterator(array('key' => 2))) . PHP_EOL;

$is = array(1,2,3);
$i = new ArrayIterator($is);
foreach ($i as $k => $v) {
  echo 'Outer: ' . $v . PHP_EOL;
  
  foreach (new ArrayIterator($i) as $k2 => $v2) {
  // foreach (new ArrayIterator($is) as $k2 => $v2) {
    echo 'Inner: ' . $v2 . PHP_EOL;
  }
}