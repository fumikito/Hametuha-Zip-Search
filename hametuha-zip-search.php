<?php
/**
 * @package hametuha_zip_search
 * @version 1.0
 * Plugin Name: Hametuha Zip Search
 * Plugin URI: http://hametuha.co.jp
 * Description: 郵便番号を検索するAjaxエンドポイントを追加します。
 * Author: Takahashi Fumiki
 * Version: 1.0
 * Author URI: http://hametuha.co.jp
 */

class Hametuha_Zip_Search{
	/**
	 * @var string
	 */
	private $version = '1.0';
	
	/**
	 * @var string
	 */
	private $table = 'zips';
	
	/**
	 * Constructor
	 * @global wpdb $wpdb
	 */
	public function __construct(){
		global $wpdb;
		$this->table = $wpdb->prefix.$this->table;
		add_action('admin_menu', array($this, 'admin_menu'));
		add_action('admin_init', array($this, 'admin_init'));
		add_action('plugins_loaded', array($this, 'activate'));
		add_action('wp_ajax_hametuha_zip_edit', array($this, 'admin_ajax'));
	}
	
	/**
	 * Hook for Put admin menu
	 */
	public function admin_menu(){
		add_options_page('郵便番号検索', '郵便番号', 'manage_options', 'zip-search', array($this, 'admin_page'));
	}
	
	/**
	 * Fire on admin panel
	 */
	public function admin_init(){
		if(isset($_GET['page']) && $_GET['page'] == 'zip-search' && $this->ajaxable()){
			wp_enqueue_script(
				'hametuha-zip-search',
				plugin_dir_url(__FILE__).'ajax.js',
				array('jquery'),
				$this->version
			);
			wp_localize_script('hametuha-zip-search', 'HametuhaZipSearch', array(
				'endpoint' => admin_url('admin-ajax.php'),
				'action' => 'hametuha_zip_edit',
				'nonce' => wp_create_nonce('hametuha_zip_edit'),
				'csv' => $this->current_csv(),
				'action_name' => $this->current_action()
			));
		}
	}
	
	public function admin_ajax(){
		global $wpdb;
		set_time_limit(0);
		if(isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'hametuha_zip_edit')){
			$complete = false;
			$done = 0;
			$current = 0;
			$offset = (int)$_POST['offset'];
			$limit = $offset + 50;
			$path = get_attached_file($_POST['csv']);
			$handle = fopen($path, 'r');
			while(!feof($handle)){
				$current++;
				$line = fgets($handle);
				if($current <= $offset){
					continue;
				}elseif($current > $limit){
					break;
				}else{
					$line = array_map(array($this, 'strip'), explode(',', $line));
					if(is_numeric($line[2])){
						$zip = $line[2];
						$pref = $line[6];
						$city = $line[7];
						$town = $line[8];
						$street = "";
						$office = '';
					}else{
						$zip = $line[7];
						$pref = $line[3];
						$city = $line[4];
						$town = $line[5];
						$street = $line[6];
						$office = $line[2];
					}
					$pref = mb_convert_encoding($pref, "utf-8", 'sjis-win');
					$city = mb_convert_encoding($city, "utf-8", 'sjis-win');
					$town = mb_convert_encoding($town, "utf-8", 'sjis-win');
					$street = mb_convert_encoding($street, "utf-8", 'sjis-win');
					$office = mb_convert_encoding($office, "utf-8", 'sjis-win');
					switch($_POST['action_name']){
						case "add":
							if($wpdb->get_var($wpdb->prepare("SELECT ID FROM {$this->table} WHERE zip = %s", $zip))){
								//Update
								$wpdb->update($this->table,array(
									'prefecture' => $pref,
									'city' => $city,
									'town' => $town,
									'street' => $street,
									'office' => $office
								), array('zip' => $zip), array('%s', '%s', '%s', '%s', '%s'), array('%s'));
							}else{
								//Insert
								$wpdb->insert($this->table, array(
									'zip' => $zip,
									'prefecture' => $pref,
									'city' => $city,
									'town' => $town,
									'street' => $street,
									'office' => $office
								), array('%s', '%s', '%s', '%s', '%s', '%s'));
							}
							$done++;
							break;
						case "delete":
							$wpdb->query($wpdb->prepare("DELETE FROM {$this->table} WHERE zip = %s", $zip));
							$done++;
							break;
						default:
							break;
					}
				}
			}
			if(feof($handle)){
				$complete = true;
			}
			header('Content-Type: application/json');
			echo json_encode(array(
				'complete' => $complete,
				'done' => $done,
				'path' => $path,
				'current' => $current
			));
			die();
		}
	}
	
	/**
	 * Create Admin Page
	 * @global wpdb $wpdb 
	 */
	public function admin_page(){
		global $wpdb;
		?>
		<div class="wrap">
			<div class="icon32" style="background: url('<?php echo plugin_dir_url(__FILE__); ?>post.png') center center no-repeat;"><br /></div>
		<h2>郵便番号検索</h2>
		<?php do_action("admin_notice"); ?>
		<?php require_once dirname(__FILE__).DIRECTORY_SEPARATOR."admin-panel.php"; ?>
		</div>
		<?php
	}
	
	/**
	 * Get CSV File
	 * @global wpdb $wpdb
	 * @return array
	 */
	private function get_csv(){
		global $wpdb;
		$sql = <<<EOS
			SELECT ID,post_title,post_mime_type
			FROM {$wpdb->posts}
			WHERE post_type = 'attachment'
			  AND post_mime_type = 'text/csv'
			ORDER BY post_date DESC
EOS;
		return $wpdb->get_results($sql);
	}
	
	/**
	 * Strips CSV file
	 * @param string $string
	 * @return string
	 */
	public function strip($string){
		return preg_replace("/^\"(.*)\"$/", '$1', $string);
	}
	
	/**
	 * Check if ajax action is possible
	 * @return boolean 
	 */
	private function ajaxable(){
		$flg = false;
		if(isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'hametuha_zip_search')){
			if(isset($_POST['action']) && false !== array_search($_POST['action'], array('add', 'delete'))){
				if(isset($_POST['csv']) && is_numeric($_POST['csv'])){
					$flg = true;
				}
			}
		}
		return $flg;
	}
	
	/**
	 * Returns Currently selected csv file.
	 * @return number
	 */
	private function current_csv(){
		if($this->ajaxable()){
			return (int)$_POST['csv'];
		}else{
			return 0;
		}
	}
	
	/**
	 * Returns currently selected actions.
	 * @return string
	 */
	private function current_action(){
		if($this->ajaxable()){
			return (string)$_POST['action'];
		}else{
			return false;
		}
	}
	
	/**
	 * Returns currently selected csv's length.
	 * @return int
	 */
	private function current_row(){
		if($this->ajaxable()){
			set_time_limit(0);
			$path = get_attached_file($_POST['csv']);
			//Read file
			$handle = fopen($path, 'r');
			$row = 0;
			while(!feof($handle)){
				$line = fgets($handle);
				$row++;
			}
			return (int)$row;
		}else{
			return 0;
		}
	}
	
	/**
	 * Returns registered number
	 * @global wpdb $wpdb
	 * @return int
	 */
	public function count_rows(){
		global $wpdb;
		return (int)$wpdb->get_var('SELECT COUNT(ID) FROM '.$this->table);
	}
	
	/**
	 * fgetcsvのShift_JIS対応版
	 * @param resource $handle
	 * @param string $length
	 * @param string $d
	 * @param string $e
	 * @return array
	 */
	private function fget_csv(&$handle, $length = null, $d = ',', $e = '"'){
		$d = preg_quote($d);
        $e = preg_quote($e);
        $_line = "";
        while ($eof != true) {
            $_line .= (empty($length) ? fgets($handle) : fgets($handle, $length));
            $itemcnt = preg_match_all('/'.$e.'/', $_line, $dummy);
            if ($itemcnt % 2 == 0) $eof = true;
        }
        $_csv_line = preg_replace('/(?:\\r\\n|[\\r\\n])?$/', $d, trim($_line));
        $_csv_pattern = '/('.$e.'[^'.$e.']*(?:'.$e.$e.'[^'.$e.']*)*'.$e.'|[^'.$d.']*)'.$d.'/';
        preg_match_all($_csv_pattern, $_csv_line, $_csv_matches);
        $_csv_data = $_csv_matches[1];
        for($_csv_i=0;$_csv_i<count($_csv_data);$_csv_i++){
            $_csv_data[$_csv_i]=preg_replace('/^'.$e.'(.*)'.$e.'$/s','$1',$_csv_data[$_csv_i]);
            $_csv_data[$_csv_i]=str_replace($e.$e, $e, $_csv_data[$_csv_i]);
        }
        return empty($_line) ? false : $_csv_data;
	}
	
	/**
	 * Execute on activation
	 */
	public function activate() {
		if(is_admin()){
			//Try to create table
			global $wpdb;
			$db_version = get_option('hametuha_zip_search_version');
			if(!$db_version || version_compare($this->version, $db_version, '>')){
				$char = defined("DB_CHARSET") ? DB_CHARSET : "utf8";
				$sql = <<<EOS
					CREATE TABLE {$this->table} (
						ID INT NOT NULL AUTO_INCREMENT,
						zip VARCHAR(10) NOT NULL,
						prefecture VARCHAR(255) NOT NULL,
						city VARCHAR(255) NOT NULL,
						town MEDIUMTEXT NOT NULL,
						street MEDIUMTEXT NOT NULL,
						office MEDIUMTEXT NOT NULL,
						PRIMARY KEY  (ID),
						INDEX  (zip(3)),
						INDEX  (prefecture(6))
					) ENGINE = MYISAM DEFAULT CHARSET = {$char};
EOS;
				require_once ABSPATH."wp-admin/includes/upgrade.php";
				dbDelta($sql);
				update_option('hametuha_zip_search_version', $this->version);
			}
		}
	}
	
	/**
	 * Delete all data
	 * @global wpdb $wpdb
	 */
	public function uninstall(){
		global $wpdb;
		$wpdb->query("DROP TABLE {$this->table}");
		delete_option('hametuha_zip_search_version');
	}
}
$hametuha_zip_search = new Hametuha_Zip_Search();