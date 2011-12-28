## WordPress Plugin Bootstrap

Simple WordPress Plugin Bootstrap that allows for easy creation of Admin CRUD actions for widgets and objects so that you can stay DRY.

# Features
* Easily generate object models with one function call that will automatically:
  * Create admin administration areas with admin links.
  * Create all necessary database tables according to class atrribute requirements.
  * Generate routes for create, read, update and delete (CRUD) for persistent storage of objects.
* Create field types for strings, blobs, decimals, booleans (check boxes), unique ids with automatic HTML form fields and WYSIWYG integration.
* Links images, CSS, and Javascripts dynamically and keeps with best practices.
* Creates timestamp columns for models: updated\_at and created\_at.
* Helpful code comments that allow for easy setup and helps avoid common and costly pitfalls.

# Installation

Coming soon. 

# Usage

```
WordPress_Plugin_Bootstrap::create_model( string $name, array $attributes );
``` 

*$name* - Name of model. For proper CRUD routing, keep singular.  
Examples: Category, Event, Widget

*$attributes* - Array of model attributes that get stored in the database with associated field types.  
Examples: 'title' => 'string', 'description' => 'text', 'active' => 'boolean'

```
# Example:
$wppb = new WordPress_Plugin_Bootstrap();
$wppb->create_model('widget', array('name'=>'string', 'description'=>'text', 'active'=>'boolean'));
```


