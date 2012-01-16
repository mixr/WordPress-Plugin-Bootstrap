<?php
class WordPress_Plugin_Model{

 /** 
  *  Admin Constructor for creating object
  *  Sets menu, and varifies database structure
  *
  *  @param string $name : unique class name 
  *  @param mixed $attr
  *          array  : fields and field types for database storage 
  *          string : "index"
  *          int    : database id
  *                      
  */
  public function __construct($name, $attr, $action = "setup", $id = NULL) {
    global $wpdb;

    # Set class attributes and determine action and show template
    $this->set_name($name,$action);
    $this->set_action($action);
    $this->class_name = strtolower(str_replace(array(" ","'"),array('_',''),$this->name));
    $this->table_name = $wpdb->prefix.'model_'.$this->class_name;
    $this->capability = "publish_posts";
    $this->attr = $attr; 
    $this->structure = $wpdb->get_results("SHOW COLUMNS FROM $this->table_name");

    if(is_admin()){
      $this->admin_slug = "wppb-manage-$this->class_name";
      $this->set_routes();
    }

    # Set action attributes based on action
    if($this->action == "setup"){
      if(function_exists('is_admin') && is_admin()){
        add_action('admin_menu', array(&$this, 'create_menu'));
        $this->verify_db();
      }
    }
    elseif($this->action == "index"){
      $ids = $wpdb->get_results("SELECT id FROM $this->table_name");
      $all_objects = array();
      foreach($ids as $id){
        $obj = new WordPress_Plugin_Model($this->name, $this->attr, 'show', $id->id);
        $all_objects[] = $obj;
      }
      $this->saved_objects = $all_objects;
      $this->set_index_headers();
    }
    elseif($this->action == "show" || $this->action == "edit"){
      $obj = $wpdb->get_results("SELECT * FROM `$this->table_name` WHERE id=$id");
      $this->data = $obj[0];
      $this->edit_url .= $id;
    }

  }



 /**
  *  Determine name from action or constructor
  */
  private function set_name($name, $action){
    if(!empty($name)) $this->name = ucfirst($name);
    elseif($action == "index"){
      if(is_admin()){
        $this->name = ucfirst(str_replace('wppb-manage-', '', $_GET['page']));
      }
    } 
  }



 /**
  *  Override $action from constructor params with GET array.
  *  Use $control to limit actions to prevent URL hacking.
  */
  private function set_action($action){
    $control = array('dispatch', 'edit', 'show', 'index');
    if($action != "setup" && !empty($_GET['action'])){
      if(in_array($_GET['action'],$control)) $this->action = $_GET['action'];
    }
    else $this->action = $action;
  }



 /**
  *  Create DB table for object and rebuild structure if necessary
  *  Relies on dbDelta
  */
  private function verify_db(){
    global $wpdb;
    $sql =  "CREATE TABLE $this->table_name (" . "\r\n";
    $sql .=   "`id` mediumint(9) NOT NULL AUTO_INCREMENT," . "\r\n";
    foreach($this->attr as $field => $field_type){
      $sql .= "`$field` ".str_replace(array('string', 'boolean'), array('VARCHAR(255)', 'TINYINT(1)'), $field_type)." NOT NULL," . "\r\n";
    }
    $sql .=   "`updated_at` timestamp default now() on update now()," . "\r\n";
    $sql .=   "UNIQUE KEY id (id)" . "\r\n";
    $sql .= ");";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
  }




 /**
  *  Create class attributes related to routes
  */
  private function set_routes(){
    $this->admin_url = admin_url()."admin.php?page=$this->admin_slug";

    // Dispatcher route: path and url
    $override = WPPB_PATH."/admin/$this->class_name/wppb-dispatcher.php";
    $this->dispatcher_path = file_exists($override) ? $override : WPPB_PATH.'admin/wppb-dispatcher.php';
    $this->dispatcher_url = $this->admin_slug;
    
    // Index route: path and url
    $override = WPPB_PATH."/admin/$this->class_name/wppb-index.php";
    $this->index_path = file_exists($override) ? $override : WPPB_PATH.'admin/wppb-index.php';
    $this->index_url = $this->admin_slug;
    
    // Edit routes
    $override = WPPB_PATH."/admin/$this->class_name/wppb-edit.php";
    $this->edit_path = file_exists($override) ? $override : WPPB_PATH.'admin/wppb-edit.php';
    $this->edit_url = $this->admin_url.'&action=edit&id=';
  }




 /**
  *  Create Admin menu for model on WP::admin_init
  */
  public function create_menu(){
    // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
    add_menu_page("WP Model ".$this->name, "Manage ".$this->name, $this->capability, $this->admin_slug, array(&$this, 'load_dispatcher'));
  }




 /**
  *  Loads index, edit, or show file for object based on $this->action
  *  Override by adding a file {sanitized_class_name}/wppb_{action}.php
  *  in admin folder.
  */
  public function load_action(){
    switch($this->action){
      case "edit":
        require_once($this->edit_path);
        break;
      case "show":
        require_once($this->show_path);
        break;
      case "index":
      case "dispatch":
        require_once($this->index_path);
        break;
    }
  }
  public function load_dispatcher(){
    require_once($this->dispatcher_path);
  }


 /**
  *  From table structure create array of headers
  *  to display on admin index table. Columns name
  *  'name' and 'title' and 'updated_at' are primary.
  */
  private function set_index_headers(){
    $headers = array();
    $primary = array('id', 'name', 'title', 'updated_at');
    foreach($this->structure as $row){
      if( in_array($row->Field, $primary) ){
        $headers[] = ucfirst($row->Field);
      }
    }
    foreach($this->structure as $row){
      if( !in_array($row->Field, $primary) ){
        if( count( $headers ) < 3 ) {
          $headers[] = ucfirst($row->Field);
        }
      }
    }
    $this->headers = $headers; 
  }



 /**
  *  Returns value for header in show
  */
  public function get_val($col){
    if(empty($col) || empty($this->data)) return '';
    $c = strtolower($col);
    return $this->data->$c;
  }

}
?>
