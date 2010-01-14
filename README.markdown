#Sample Usage
<pre>
class Controller_Welcome extends Controller {

	public function action_index()
	{
		$this->request->response = 'hello, world!';

		$foo = 'foo';

		Event::add('test_one', array($this, 'one'));
		Event::add('test_two', array($this, 'two'));

		Event::run('test_one', $foo);

		echo $foo.'<br />';
	}

	public function one()
	{
		echo Event::$data.'<br />';

		Event::$data = 'bar';

		$blah = 'inner_foo';

		Event::run('test_two', $blah);

		echo $blah.'<br />';

		echo Event::$data.'<br />';
	}

	public function two()
	{
		echo Event::$data.'<br />';

		Event::$data = 'inner_foo_modified!';
	}

} // End Welcome
</pre>
## Output
<pre>
foo
inner_foo
inner_foo_modified!
bar
bar
hello, world!
</pre>