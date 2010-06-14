<?php
class commands_generator {
    
    protected $console = null;
    protected $template_dir = null;
    protected $destination_dir = null;
    protected $namespace = '';
    protected $name = '';
    protected $pluralized_name = '';

    function __construct($console=null) {
        $this->console = $console;
        $this->template_dir = 'inc/commands/generator/templates/';
        $this->destination_dir = 'app/';
        $this->migration_path = "db/migrations/";
    }

    function generate() {
        // Setup
        $this->model = $this->console->argument(1);
        if (strstr($this->model, '/')) {
            list($this->namespace, $this->model) = explode('/', $this->model);
        }
        $this->pluralized_name = api_helpers_string::plural($this->model);
        $this->controller = api_helpers_string::plural($this->model);
        $this->controller_path = $this->destination_dir.'controllers/'.($this->namespace != '' ? $this->namespace.'/' : '');
        $this->url = '/'.($this->namespace != '' ? $this->namespace.'/' : '').$this->controller;
        $this->view_path = $this->destination_dir.'views/'.($this->namespace != '' ? $this->namespace.'/' : '').$this->controller;

        switch($this->console->argument(0)) {
        case 'controller':
            // scripts/generate.php controller sction action action
            echo "Generate Controller\n";
            $this->generateController();
            $a = $this->console->countArguments();
            for($i=2;$i<$a;$i++) {
                $this->generateView($this->console->argument($i), false);
            }
            break;
        case 'model':
            echo "generate model\n";
            $this->generateModel();
            break;
        case 'mapper':
            echo "generate mapper\n";
            $this->generateMapper();
            break;
        case 'migration':
            $column = explode('_', $this->console->argument(1));
            $action = $column[0];
            echo "Generate migration\n";
            if ($action == "add") {
                $this->generateMigrationChange();
            } else {
                $this->generateMigrationCreate();
            }
            break;
        case 'scaffold':
            // generate.php scaffold realm name:string url:string blah:integer
            echo "generate scaffold\n";
            $this->generateScaffold();
            break;
        default:
            echo "Usage: ...\n";
        }
    }

    protected function generateMigrationCreate($fields=array()) {
        $migration = file_get_contents($this->template_dir.'migration_create.php');
        $table_name = $this->controller;
        if (strstr($table_name, 'create_')) {
            $table_name = str_replace('create_', '', $table_name);
        }
        $migration = str_replace("name", $table_name, $migration);

        $field_string = '';
        if (!in_array('id', $fields)) {
            array_unshift($fields, "id:integer");
        }
        foreach($fields as $field) {
            list($f, $type) = explode(':', $field);
            $f = trim( $f);
            $type = trim($type);
            $field_string .= "'$f' => array('$type'),\n\t\t";
        }
        $field_string = rtrim($field_string);
        $migration = str_replace('fields', $field_string, $migration);
        $filename = date("Ymdhis").'_create_'.$table_name.'.php';
        file_put_contents($this->migration_path.$filename, $migration);
    }

    protected function generateMigrationChange() {
        $migration = file_get_contents($this->template_dir.'migration.php');
        $table_name = $this->controller;
        $column = explode('_', $table_name);
        $action = $column[0];
        $column = $column[count($column)-1];
        echo $column."\n";
        echo $table_name."\n";

        $fields=array();
        for($i=2; $i < $this->console->countArguments(); $i++) {
            $fields[] = $this->console->argument($i)."\n";
        }

        $migration = str_replace("name", $table_name, $migration);

        $up_field_string = '';
        foreach($fields as $field) {
            list($f, $type) = explode(':', $field);
            $f = trim( $f);
            $type = trim($type);
            switch($action) {
            case 'add':
                $up_field_string .= "\$this->add_column('$column', '$f', '$type');\n\t";
                break;
            }
        }
        $up_field_string = rtrim($up_field_string);
        $migration = str_replace('up_fields', $up_field_string, $migration);

        $down_field_string = '';
        foreach($fields as $field) {
            list($f, $type) = explode(':', $field);
            $f = trim( $f);
            $type = trim($type);
            switch($action) {
            case 'add':
                $down_field_string .= "\$this->remove_column('$column', '$f', '$type');\n\t";
                break;
            }
        }
        $down_field_string = rtrim($down_field_string);
        $migration = str_replace('down_fields', $down_field_string, $migration);
        $filename = date("Ymdhis").'_'.$table_name.'.php';
        echo $migration;
        echo "\n";
        file_put_contents($this->migration_path.$filename, $migration);
    }

    protected function generateModel() {
        $filename = '';
        $model = '';

        //$name = $this->console->argument(1);
        $filename = $this->name.'.php';

        $model = file_get_contents($this->template_dir.'model.php');
        $model = str_replace("name", $this->name, $model);

        $this->destination_dir .= 'models/';
        if (!is_file($this->destination_dir.$filename)) {
            echo "create: ". $this->destination_dir.$filename."\n";
            file_put_contents($this->destination_dir.$filename, $model);
        } else {
            echo "exists: ".$this->destination_dir.$filename."\n";
        }
    }

    protected function generateMapper() {
        $filename = '';
        $model = '';

        //$name = $this->console->argument(1);
        //$pluralized_name = api_helpers_string::plural($name);
        $filename = $this->pluralized_name.'.php';

        $mapper = file_get_contents($this->template_dir.'mapper.php');
        $mapper = str_replace("nameplural", $pluralized_name, $mapper);
        $mapper = str_replace("name", $name, $mapper);

        $this->destination_dir .= 'mappers/';
        if (!is_file($this->destination_dir.$filename)) {
            echo "create: ". $this->destination_dir.$filename."\n";
            file_put_contents($this->destination_dir.$filename, $mapper);
        } else {
            echo "exists: ".$this->destination_dir.$filename."\n";
        }
    }

    protected function generateView($action='index') {
        $filename = $action.'.php';
        $view = '';

        $controller = $this->name; //console->argument(1);
        $dir = $this->pluralized_name; //api_helpers_string::plural($controller);
        $filename = $dir.'/'.$filename;

        $view = file_get_contents($this->template_dir.'view_empty.php'); 

        if ($this->namespace != '') {
            $filename = $namespace.'/'.$filename;
        }

        $filename = 'view/'.$filename;

        $view = str_replace("filename", $this->destination_dir.$filename, $view);
        $this->destination_dir .= 'mappers/';
        if (!is_file($this->destination_dir.$filename)) {
            echo "create: ". $this->destination_dir.$filename."\n";
            file_put_contents($this->destination_dir.$filename, $controller);
        } else {
            echo "exists: ".$this->destination_dir.$filename."\n";
        }
    }

    protected function generateScaffoldIndexView($model, $fields=array()) {
        $filename = $this->view_path.'/index.php';
        $view = '';

        $view = file_get_contents($this->template_dir.'view_index.php'); 

        $view = str_replace("url", $this->url, $view);
        $view = str_replace("nameplural", $this->pluralized_name, $view);
        $view = str_replace("name", $this->model, $view);

        $field_string = '';
        // insert fields
        foreach($fields as $field) {
            list($f, $type) = explode(":", $field);
            $field_string .= "<?=$model->$f?> - \n";
        }
        $field_string = rtrim($field_string, " - \n");
        $view = str_replace("fields",  $field_string, $view);

        $this->destination_dir .= 'mappers/';
        if (!is_file($filename)) {
            echo "create: ". $filename."\n";
            file_put_contents($filename, $view);
        } else {
            echo "exists: ".$filename."\n";
        }
        echo $view;
    }

    protected function generateScaffoldShowView($model, $fields=array()) {
        $filename = $this->view_path.'/show.php';
        $view = file_get_contents($this->template_dir.'view_show.php');

        $view = str_replace("url", $this->url, $view);
        $view = str_replace("nameplural", $this->pluralized_name, $view);
        $view = str_replace("name", $model, $view);

        $field_string = '';
        // insert fields
        foreach($fields as $field) {
            list($f, $type) = explode(":", $field);
            $field_string .= "<li><?=$model->$f?></li>";
        }
        $view = str_replace("fields",  $field_string, $view);

        if (!is_file($filename)) {
            echo "create: ". $filename."\n";
            file_put_contents($filename, $view);
        } else {
            echo "exists: ".$filename."\n";
        }
    }

    protected function generateScaffoldEditView($model, $fields=array()) {
        $filename = $this->view_path.'/edit.php';
        $view = file_get_contents($this->template_dir.'view_edit.php');
        
        $field_string = $this->getViewForm($fields, $model);
        $view = str_replace('url', $this->url, $view);
        $view = str_replace('name', $model, $view);
        $view = str_replace("fields",  $field_string, $view);

        if (!is_file($filename)) {
            echo "create: ". $filename."\n";
            file_put_contents($filename, $view);
        } else {
            echo "exists: ".$filename."\n";
        }
    }

    protected function generateScaffoldNewView($model, $fields) {
        $filename = $this->view_path.'/new.php';
        $view = file_get_contents($this->template_dir.'view_new.php');

        $field_string = $this->getViewForm($fields, $model, false);
        $view = str_replace('url', $this->url, $view);
        $view = str_replace('name', $model, $view);
        $view = str_replace("fields",  $field_string, $view);

        if (!is_file($filename)) {
            echo "create: ". $filename."\n";
            file_put_contents($filename, $view);
        } else {
            echo "exists: ".$filename."\n";
        }
    }

    private function getViewForm($fields, $model, $id=true) {
        $field_string = '';
        if ($id) {
            $field_string = "\n\t<?= \$this->hiddenfieldfor(\$this->name, 'id') ?>";
        }
        // insert fields
        foreach($fields as $field) {
            list($f, $type) = explode(":", $field);
            if (trim($type) == 'string') {
                $field_string .= "\n\t<?= \$this->textfieldfor(\$this->$model, '$f') ?>";
            }
        }
        return ltrim($field_string, "\n ");
    }

    protected function generateScaffoldController($name) {
        $filename = '';
        $controller = '';

        //$name = $this->console->argument(1);
        if (strstr($name, '_')) {
            list($this->namespace, $name) = explode('_', $name);
        }
        $pluralized_name = api_helpers_string::plural($name);

        $filename = $pluralized_name.'.php';
        $controller = file_get_contents($this->template_dir.'controller.php'); 
        if ($this->namespace != '') {
            $filename = $this->namespace.'/'.$filename;
            $controller = str_replace("namespace", $this->namespace, $controller);
        } else {
            $controller = str_replace("namespace/", "", $controller);
        }
        $controller = str_replace("nameplural", $pluralized_name, $controller);
        $controller = str_replace("name", $name, $controller);

        $this->destination_dir .= 'controllers/';

        if (!is_file($this->destination_dir.$filename)) {
            echo "create: ". $this->destination_dir.$filename."\n";
            file_put_contents($this->destination_dir.$filename, $controller);
        } else {
            echo "exists: ".$this->destination_dir.$filename."\n";
        }
    }

    protected function generateScaffold() {
        $fields = array();
        echo $this->name."\n";
        //$name = $this->console->argument(1);
        
        $views = array('index', 'show', 'edit', 'new');

        for($i=2; $i < $this->console->countArguments(); $i++) {
            $fields[] = $this->console->argument($i)."\n";
        }

        // Check if directory and file exists
        if (!is_dir($this->controller_path)) {
            echo "create: $this->controller_path\n";
            mkdir($this->controller_path);
        } else {
            echo "exists: $this->controller_path\n";
        }

        // Generate controller
        $this->generateScaffoldController($this->name);

        // generate views
        // Check if directory and files exists
        if (!is_dir($this->view_path)) {
            echo "create: $this->view_path\n";
            mkdir($this->view_path);
        } else {
            echo "exists: $this->view_path\n";
        }
//        foreach($views as $view) {
        $this->generateScaffoldIndexView($this->name, $fields);
        $this->generateScaffoldShowView($this->name, $fields);
        $this->generateScaffoldEditView($this->name, $fields);
        $this->generateScaffoldNewView($this->name, $fields);
        //        
        //        

        /* Create migration */
        if (!is_dir($this->migration_path)) {
            echo "create: $this->migration_path\n";
            mkdir($this->migration_path);
        } else {
            echo "exists: $this->migration_path\n";
        }
        $this->generateMigrationCreate($fields);        
    }
}
