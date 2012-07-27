<?php

require path('app') . '/tasks/generate.php';

class Generate_Test extends PHPUnit_Framework_TestCase
{
	public static $model;
	public static $controller;
	public static $migration;
	public static $view;

	public function setup()
	{
		// We don't care about the echos
		ob_start();

		self::$model = path('app') . '/models/book.php';
		self::$controller = path('app') . '/controllers/admins.php';
		self::$migration = path('app') . '/migrations/';
		self::$view = path('app') . 'views/';

		$this->generate = new Generate_Task;
	}

	// @group models
	public function test_can_create_model_file()
	{
		$this->generate->model('Book');

		$this->assertFileExists(self::$model);
	}


	// @group controllers
	public function test_can_create_controller_file()
	{
		$this->generate->controller(array(
			'Admin'
		));

		$this->assertFileExists(self::$controller);
	}


	public function test_can_add_actions()
	{
		$this->generate->controller(array(
			'Admin',
			'index',
			'show'
		));

		$contents = File::get(self::$controller);

		$this->assertContains('action_index', $contents);
		$this->assertContains('action_show', $contents);
	}


	public function test_controllers_can_be_restful()
	{
		$this->generate->controller(array(
			'admin',
			'index',
			'index:post',
			'update:put',
			'restful'
		));

		$contents = file::get(self::$controller);

		$this->assertContains('public $restful = true', $contents);
		$this->assertContains('get_index', $contents);
		$this->assertContains('post_index', $contents);
		$this->assertContains('put_update', $contents);
	}


	public function test_restful_can_be_any_argument()
	{
		$this->generate->controller(array(
			'admin',
			'restful',
			'index:post',
		));

		$contents = file::get(self::$controller);

		$this->assertContains('public $restful = true', $contents);
		$this->assertContains('post_index', $contents);
	}


	// @group migrations
	public function test_can_create_migration_files()
	{
		$this->generate->migration(array(
			'create_users_table'
		));

		$file = File::latest(self::$migration);
		$this->assertFileExists((string)$file);
	}


	public function test_migration_offers_boilerplate_code()
	{
		$this->generate->migration(array(
			'create_users_table'
		));

		$file = File::latest(self::$migration);
		$contents = File::get($file);

		$this->assertContains('class Create_Users_Table', $contents);
		$this->assertContains('public function up', $contents);
		$this->assertContains('public function down', $contents);
	}


	public function test_migration_sets_up_create_schema()
	{
		$this->generate->migration(array(
			'create_users_table',
			'id:integer',
			'email:string'
		));

		$file = File::latest(self::$migration);
		$contents = File::get($file);

		$this->assertContains('Schema::create', $contents);
		$this->assertContains("\$table->increments('id')", $contents);
		$this->assertContains("\$table->string('email')", $contents);

		// Dropping too
		$this->assertContains("Schema::drop('users')", $contents);
	}


	public function test_migration_sets_up_add_schema()
	{
		$this->generate->migration(array(
			'add_user_id_to_posts_table',
			'user_id:integer'
		));

		$file = File::latest(self::$migration);
		$contents = File::get($file);

		$this->assertContains("Schema::table('posts'", $contents);
		$this->assertContains("\$table->integer('user_id')", $contents);
		$this->assertContains("\$table->drop_column('user_id')", $contents);
	}
	

	// @group views
	public function test_can_create_views()
	{
		$this->generate->view(array(
			'book',
			'test'
		));

		// Views default to blade
		$this->assertFileExists(self::$view . 'book.blade.php');
		$this->assertFileExists(self::$view . 'test.blade.php');
	}


	public function test_can_create_nested_views()
	{
		$this->generate->view(array(
			'book.index',
			'book.admin.show',
			'book'
		));

		$this->assertFileExists(self::$view . 'book/index.blade.php');
		$this->assertFileExists(self::$view . 'book/admin/show.blade.php');
		$this->assertFileExists(self::$view . 'book.blade.php');
	}


	// @group resource
	public function test_can_create_resources()
	{
		$this->generate->resource(array(
			'user',
			'index',
			'show'
		));

		$this->assertFileExists(self::$view . 'user/index.blade.php');
		$this->assertFileExists(self::$view . 'user/show.blade.php');

		$this->assertFileExists(path('app') . 'models/user.php');
		$this->assertFileExists(path('app') . 'controllers/users.php');
	}

	public function tearDown()
	{
		ob_end_clean();

		File::delete(path('app') . 'controllers/admin.php');
		File::cleandir(path('app') . 'models');
		File::cleandir(path('app') . 'migrations');
		File::cleandir(path('app') . 'views');
	}
}