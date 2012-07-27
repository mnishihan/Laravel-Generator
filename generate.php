<?php

/**
 * Laravel Generator
 * 
 * Rapidly create files, methods, and schema.
 *
 * USAGE:
 * Add this file to your Laravel application/tasks directory
 * and call the methods with: php artisan generate:[model|controller|migration] [args]
 * 
 * See individual methods for additional usage instructions.
 * 
 * @author      Jeffrey Way <jeffrey@jeffrey-way.com>
 * @license     haha - whatever you want.
 * @version     0.8
 * @since       July 26, 2012
 *
 */
class Generate_Task 
{

    public static $css_dir = 'css/';
    public static $js_dir  = 'js/';

    /**
     * Time Savers
     *
     */
    public function c($args) { return $this->controller($args); }
    public function m($args) { return $this->model($args); }
    public function mig($args) { return $this->migration($args); }
    public function v($args) { return $this->view($args); }
    public function a($args) { return $this->assets($args); }
    public function r($args) { return $this->resource($args); }


    /**
     * Generate a controller file with optional actions.
     *
     * USAGE:
     * 
     * php artisan generate:controller Admin
     * php artisan generate:controller Admin index edit
     * php artisan generate:controller Admin index index:post restful
     * 
     * @param  $args array  
     * @return string
     */
    public function controller($args)
    {
        if ( empty($args) ) {
            echo "Error: Please supply a class name, and your desired methods.\n";
            return;
        }

        // Name of the class and file
        $class_name = Str::plural(ucwords(array_shift($args)));

        // Where will this file be stored?
        $file_path = path('app') . 'controllers/' . strtolower("$class_name.php");

        // Begin building up the file's content
        $content = "<?php class {$class_name}_Controller extends Base_Controller { ";

        // Let's see if they added "restful" anywhere in the args.
        $restful_pos = array_search('restful', $args);
        if ( $restful_pos !== false ) {
            array_splice($args, $restful_pos, 1);
            $restful = true;
            $content .= "public \$restful = true;";
        }

        // Now we filter through the args, and create the funcs.
        foreach($args as $method) {
            // Were params supplied? Like index:post?
            if ( strpos($method, ':') !== false ) {
                list($method, $verb) = explode(':', $method);
                $content .= "public function {$verb}_{$method}() ";
            } else {
                $action = empty($restful) ? "action" : "get";
                $content .= "public function {$action}_{$method}() ";
            }

            $content .= "{}";
        }

        // Close class
        $content .= "}";

        // Prettify
        $content = $this->prettify($content);

        // Create the file
        $this->write_to_file($file_path, $content);
    }


    /**
     * Generate a model file + boilerplate. (To be expanded.)
     *
     * USAGE
     *
     * php artisan generate:model User
     *
     * @param  $args array  
     * @return string
     */
    public function model($args)
    {
        // Name of the class and file
        $class_name = is_array($args) ? ucwords($args[0]) : ucwords($args);

        $file_path = path('app') . 'models/' . strtolower("$class_name.php");

        // Begin building up the file's content
        $content = "<?php class $class_name extends Eloquent {}";

        $content = $this->prettify($content);

        // Create the file
        $this->write_to_file($file_path, $content);
    }


    /**
     * Generate a migration file + schema
     *
     * INSTRUCTIONS:
     * - Separate each word with an underscore
     * - Name your migrations according to what you're doing
     * - Try to use the `table` keyword, to hint at the table name: create_users_table
     * - Use the `add`, `create`, `update` and `delete` keywords, according to your needs.
     * - For each field, specify its name and type: id:integer, or body:text
     * - You may also specify additional options, like: age:integer:nullable, or email:string:unique
     *
     *
     * USAGE OPTIONS
     *
     * php artisan generate:migration create_users_table
     * php artisan generate:migration create_users_table id:integer email:string:unique age:integer:nullable
     *
     * php artisan generate:migration add_user_id_to_posts_table user_id:integer
     *
     * php artisan generate:migration delete_active_from_users_table active:boolean
     *
     * @param  $args array  
     * @return string
     */
    public function migration($args)
    {
        if ( empty($args) ) {
            echo "Error: Please provide a name for your migration.\n";
            return;
        }

        // Name of the class and file
        $class_name = array_shift($args);

        // Determine what the table name should be.
        $table_name = $this->parse_table_name($class_name);

        // Capitalize where necessary
        // a_simple_string => A_Simple_String
        $class_name = implode('_', array_map('ucwords', explode('_', $class_name)));

        // Let's create the path to where the migration will be stored.
        $file_path = path('app') . 'migrations/' . date('Y_m_d_His') . strtolower("_$class_name.php");


        // Determine what type of event is occurring.
        // Creating a table? Adding a column? Deleting one?
        preg_match('/delete|update|add(?=_)/i', $class_name, $matches);
        if ( !empty($matches) ) {
            $table_action = 'table';
            $table_event = strtolower($matches[0]);
        } else {
            $table_action = $table_event = 'create';
        }


        // Now, we begin creating the contents of the file.
        $content = "<?php class $class_name {"
                 . "public function up() { "
                 . "Schema::$table_action('$table_name', function(\$table) {";

        /*
        |--------------------------------------------------------------------------
        | When Deleting a Column
        |--------------------------------------------------------------------------
        */

        if ( $table_event === 'delete' ) {
            $content .= $this->drop_columns($args);
        } 


        /*
        |--------------------------------------------------------------------------
        | When Creating, Adding, or Updating Columns
        |--------------------------------------------------------------------------
        */
        if ( preg_match('/create|add|update/', $table_event) ) {
            // Build up the schema
            $content .= $this->add_columns($args);

            // Let's only add timestamps if we're creating a table for the first time.
            if ( $table_action === 'create' ) {
                $content .= "\$table->timestamps();";
            }
        }

        $content .= "});} public function down() {";


        /*
        |--------------------------------------------------------------------------
        | Create Reversal
        |--------------------------------------------------------------------------
        */
        if ( $table_event === 'create' ) {
            $content .= "Schema::drop('$table_name');";


        /*
        |--------------------------------------------------------------------------
        | Add Column(s) Reversal
        |--------------------------------------------------------------------------
        */
        } else if ( $table_event == 'add' || $table_event == 'update' ) {
            $content .= "Schema::table('$table_name', function(\$table) {";

            if ( $table_event !== 'update' ) {
                $content .= $this->drop_columns($args);
            }

            $content .= "});";


        /*
        |--------------------------------------------------------------------------
        | Delete Reversal
        |--------------------------------------------------------------------------
        */
        } else if ( $table_event == 'delete' ) {
            $content .= "Schema::table('$table_name', function(\$table) {";
            $content .= $this->add_columns($args);
            $content .= "});";
        }

        $content .= "}}";

        // prettify
        $content = $this->prettify($content);

        // Create the file
        return $this->write_to_file($file_path, $content);
    }


    /**
     * Create any number of views
     *
     * USAGE:
     *
     * php artisan generate:view home show
     * php artisan generate:view home.index home.show --blade=false
     *
     * @param $args array
     * @return void
     */
    public function view($paths)
    {
        $blade = isset($_SERVER['CLI']['BLADE'])
                    ? $_SERVER['CLI']['BLADE']
                    : true;

        foreach( $paths as $path ) {
            $dir_path = path('app') . 'views/' . str_replace('.', '/', $path);

            // create the file
            $file_path = $blade ? $dir_path . '.blade.php' : $dir_path . '.php';
            $this->write_to_file($file_path, "This is the $file_path view");
        }
    }


    /**
     * Create assets in the public directory
     *
     * USAGE:
     * php artisan generate:assets style1.css some_module.js
     * 
     * @param  $assets array
     * @return void
     */
    public function assets($assets)
    {
        foreach( $assets as $asset ) {
            $path = path('public');

            // What type of file? CSS, JS?
            $ext = pathinfo($asset);
            if ( !isset($ext['extension']) ) {
                // Hmm - not sure what to do.
                echo "Warning: Could not determine file type. Please specify an extension.";
                continue;
            }

            // Set the path, dependent upon the file type.
            switch ($ext['extension']) {
                case 'js':
                    $path = $path . self::$js_dir . $asset;
                    break;

                case 'css':

                default:
                    $path = $path . self::$css_dir . $asset;
                    break;
            }

            $this->write_to_file($path, '');
        }
    }


    /**
     * Generate resource (model, controller, and views)
     *
     * @param $args array  
     * @return void
     */
    public function resource($args)
    {
        $this->controller($args);

        $resource_name = array_shift($args);

        // Let's take any supplied view names, and set them
        // in the resource name's directory.
        $views = array_map(function($val) use($resource_name) {
            return "{$resource_name}.{$val}";
        }, $args);

        $this->view($views);
        
        $this->model($resource_name);
    }


    /**
     * Add columns
     *
     * Filters through the provided args, and builds up the schema text.
     *
     * @param  $args array  
     * @return string
     */
    protected function add_columns($args)
    {
        $content = '';

        // Build up the schema
        foreach( $args as $arg ) {
            // Like age, integer, and nullable
            @list($field, $type, $setting) = explode(':', $arg);

            if ( !$type ) {
                echo "There was an error in your formatting. Please try again. Did you specify both a field and data type for each? age:int\n";
                die();
            }

            // Primary key check
            if ( $field === 'id' and $type === 'integer' ) {
                $rule = "\$table->increments('id')";
            } else {
                $rule = "\$table->$type('$field')";

                if ( !empty($setting) ) {
                    $rule .= "->{$setting}()";
                }
            }

            $content .= $rule . ";";
        }

        return $content;
    }


    /**
     * Drop Columns
     *
     * Filters through the args and applies the "drop_column" syntax
     *
     * @param $args array  
     * @return string
     */
    protected function drop_columns($args)
    {
        $fields = array_map(function($val) {
            $bits = explode(':', $val);
            return "'$bits[0]'";
        }, $args);

        if ( count($fields) > 1 ) {
            $content = "\$table->drop_column(array(" . implode(', ', $fields) . "));";
        } else {
            $content = "\$table->drop_column($fields[0]);";
        }

        return $content;
    }


    /**
     * Figure out what the name of the table is
     *
     * Fetch the value that comes right before "_table"
     * Or try to grab the very last word that comes after "_" - create_*users*
     * If all else fails, return a generic "TABLE", to be filled in by the user.
     *
     * @param  $class_name string  
     * @return string
     */
    protected function parse_table_name($class_name)
    {
        // Try to figure out the table name
        // We'll use the word that comes immediately before "_table"
        // create_users_table => users
        preg_match('/([a-zA-Z]+)_table/', $class_name, $matches);

        if ( empty($matches) ) {
            // Or, if the user doesn't write "table", we'll just use
            // the text at the end of the string
            // create_users => users
            preg_match('/_([a-zA-Z]+)$/', $class_name, $matches);
        }

        // Hmm - I'm stumped. Just use a generic name.
        return empty($matches)
            ? "TABLE"
            : $matches[1];
    }


    /**
     * Write the contents to the specified file
     *
     * @param  $file_path string
     * @param $content string
     * @param $type string [model|controller|migration]  
     * @return void
     */
    protected function write_to_file($file_path, $content, $success = '')
    {
        if ( empty($success) ) {
            $success = "Create: $file_path.\n";
        }

        if ( File::exists($file_path) ) {
            // we don't want to overwrite it
            echo "Warning: File already exists at $file_path\n";
            return;
        }

        // As a precaution, let's see if we need to make the folder.
        File::mkdir(dirname($file_path));

        if ( File::put($file_path, $content) !== false ) {
            echo $success;
        } else {
            echo "Whoops - something...erghh...went wrong!\n";
        }
    }


    /**
     * Crazy sloppy prettify. TODO - Cleanup
     *
     * @param  $content string  
     * @return string
     */
    public function prettify($content)
    {
        $content = str_replace('<?php ', "<?php\n\n", $content);
        $content = str_replace('{}', "\n{\n\n}", $content);
        $content = str_replace('public', "\n\n\tpublic", $content);
        $content = str_replace("() \n{\n\n}", "()\n\t{\n\n\t}", $content);
        $content = str_replace('}}', "}\n\n}", $content);

        // Migration-Specific
        $content = preg_replace('/ ?Schema::/', "\n\t\tSchema::", $content);
        $content = preg_replace('/\$table(?!\))/', "\n\t\t\t\$table", $content);
        $content = str_replace('});}', "\n\t\t});\n\t}", $content);
        $content = str_replace(');}', ");\n\t}", $content);
        $content = str_replace("() {", "()\n\t{", $content);

        return $content;
    }
}


