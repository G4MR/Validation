# Example Usage

```
$name = ['name' => 'ss', 'email' => 'ss'];
$validation = new Validation($name);
$messages = [
	'name' => [
		'min' 		=> "The minimum length of $0",
		'length' 	=> "Your {field} needs to be the length of $0",
		'required' 	=> "Your {field} shouldn't be left blank",
	]
];
$validation->setRules([
	'name' => 'required|length:5|min:3',
	'email' => 'required|email',
], $messages)
->stopRules(['name' => 'required|length'])
->stopFields(['name']);

//passing variables
$length = 5;
$validation->setRules([
	'name' => [
		'required',
		'length' => [$length]
	]
]);
// $validation->debug();
var_dump($validation->isValid(), $validation->errors());
```

### Function Clarifications

* `stopRules()` - say you want the validator to check one field before
processing the next set of validation rules that you've provided? Well
this function allows you to do that.

* `stopFields()` - similar to stopRules except it stops processing errors
until all the rules for the current validation is set.  So you need to 
order them accordingly.

### Validation Rule Files

You can load validation rule files by sending the file path as the second
argument which returns an array to the `Validation()` constructor.  The
array from the file will be merged with the default validation rules

```
$path_to_file 	= 'path/to/file.php';
$validation 	= new Validation($input, $path_to_file);
```

### Adding Callbacks

Call the `makeCallback()` static anytime before the is_valid() function

```
Validation::makeCallback('validationRule', function($field, $value, $params...) {
	//...
});
```

You can use any amount of parameters after the first two values because
the first two input values will always be the array key value and the array
value that's being passed to the validation class.

Return true if everything went okay and false if it didn't.  In the default
`min` validation callback which can be seen at the bottom of the validation
class you'll see me returning the truest form of the input value.  For example
if the current input value is the minimum value return true (no problem) else
return false.  Keeping it as simple as possible.  Check source for example
validation rules.  