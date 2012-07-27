<pre>
   __                           _             
  / /  __ _ _ __ __ ___   _____| |            
 / /  / _` | '__/ _` \ \ / / _ \ |            
/ /__| (_| | | | (_| |\ V /  __/ |            
\____/\__,_|_|  \__,_| \_/ \___|_|            
                                              
   ___                          _             
  / _ \___ _ __   ___ _ __ __ _| |_ ___  _ __ 
 / /_\/ _ \ '_ \ / _ \ '__/ _` | __/ _ \| '__|
/ /_\\  __/ | | |  __/ | | (_| | || (_) | |   
\____/\___|_| |_|\___|_|  \__,_|\__\___/|_|   
                                              
</pre>

On its own, when running migrations, Laravel simply creates the specified file, and adds a bit of boilerplate code. It's then up to you to fill in the Schema and such. ...Well that's a pain.

This generator task will fill in the gaps. It can generate several things:

- Controllers and actions
- Models
- Views
- Migrations and schema
- Resources

## Installation

Simply download the `generate.php` file from this repo, and save it to your `application/tasks/` directory. Now, we can execute the various methods from the command line, via Artisan.

### Controllers

To generate a controller for your project, run:

```bash
  php artisan generate:controller Admin
```

This will create a file, `application/controllers/admin.php`, with the following contents:

```php
<?php 

class Admin_Controller extends Base_Controller 
{

}
```

However, we can also create methods/actions as well.

```bash
  php artisan generate:controller Admin index show edit
```

The arguments that we specify after the controller name (Admin) represent the methods that we desire.

```php
<?php 

class Admin_Controller extends Base_Controller 
{

	public function action_index()
	{

	}

	public function action_show()
	{

	}

	public function action_edit()
	{

	}

}
```

But, what if we want to use restful methods? Well just add `restful` as any argument, like this:

```bash
php artisan generate:controller Admin restful index index:post
```

That will produce.

```php
<?php 

class Admin_Controller extends Base_Controller 
{

	public $restful = true;

	public function get_index()
	{

	}

	public function post_index()
	{

	}

}
```

Sweet! It's-a-nice. When using restful methods, you can specify the verb after a `:` - `index:post` or maybe `update:put`.

So that's the controller generator.


### Migrations

We can also generate migrations and schema. Let's say that we need to create a users table in the DB. Type:

```bash
php artisan generate:migration create_users_table
```

Notice that we separate each word with an underscore, and use a keyword, like `create`, `update`, `delete`, or `add`.

This will create a new file in `application/migrations/` that contains:

```php
<?php 
    
class Create_Users_Table
{

    public function up()
    {
        Schema::create('users', function($table)
        {
			$table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('users');
	}

}
```

Not bad, not bad. But what about the schema? Let's specify the fields for the table.

```bash
php artisan generate:migration create_users_table id:integer name:string email_address:string
```

That will give us:

```php
<?php 
    
class Create_Users_Table
{

    public function up()
    {
        Schema::create('users', function($table)
        {
			$table->increments('id');
			$table->string('name');
			$table->string('email_address');
			$table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('users');
	}

}
```

Ahh hell yeah. Notice that it automatically applies the `timestamps()`. A bit opinionated maybe, but it only takes a second to delete, if you don't want them.

You can also specify other options, such as making your `email` field unique, or the `age` field optional - like this:

```bash
php artisan generate:migration create_users_table id:integer name:string email_address:string:unique age:integer:nullable
```

Now we get:

```php
<?php 
    
class Create_Users_Table
{

    public function up()
    {
        Schema::create('users', function($table)
        {
			$table->increments('id');
			$table->string('name');
			$table->string('email_address')->unique();
			$table->integer('age')->nullable();
			$table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('users');
	}

}
```

Sweet!

#### Keywords

It's important to remember that the script tries to figure out what you're trying to accomplish. So it's important that you use keywords, like "add" or "delete". Here's some examples:

```bash
php artisan generate:migration add_user_id_to_posts_table user_id:integer
php artisan generate:migration delete_user_id_from_posts_table user_id:integer
php artisan generate:migration create_posts_table title:string body:text
```

There's three best practices worth noting here:

1. Our class/file names are very readable. That's important.
2. The script uses the word that comes before "_table" as the table name. So, for `create_users_table`, it'll set the table name to `users`.
3. I've used the CRUD keywords to describe what I want to do.

If we run the second example:

```bash
php artisan generate:migration delete_user_id_from_posts_table user_id:integer
```

...we get:

```php
<?php 
    
class Delete_User_Id_From_Posts_Table
{

    public function up()
    {
        Schema::table('posts', function($table)
        {
			$table->drop_column('user_id');
        });
    }

    public function down()
    {
        Schema::table('posts', function($table)
        {
			$table->integer('user_id');
		});
	}

}
```

### Views

Views are a cinch to generate.

```bash
php artisan generate:view index show
```

This will create two files within the `views` folder:

1. index.blade.php
2. show.blade.php

While Blade is the default, if you'd prefer to not use it, simply add `--blade=false`, like this:

```bash
php artisan generate:view index show --blade=false
```

You can also specify subdirectories, via the period.

```bash
php artisan generate:view home.index home.edit
```

Which will create:

1. home/index.blade.php
2. home/edit.blade.php

### Resources

But what if you want to save some time, and add a few things at once? Generating a resource will produce a 
controller, model, and view files. For example:

```bash
    php artisan generate:resource post index show
```

This will create:

1. A `post.php` model
2. A `posts.php` controller with index + show actions
3. A post views folder with `index.blade.php` and `show.blade.php` views.

### Putting It All Together

So let's rapidly generate a resource for blog posts.

```bash
php artisan generate:resource post index show
php artisan generate:migration create_posts_table id:integer title:string body:text
php artisan migrate 
```

Of course, if you haven't already installed the migrations table, you'd do that first: `php artisan migrate:install`.

With those three lines of code, you now have:

1. A controller with two actions
2. A post model
3. a post views folder with two views: index and show
4. A new `posts` table in your db with id, title, and body fields.

Nifty, ay?

## Coming Soon

1. Code cleanup
2. Generate tests