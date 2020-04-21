<?

# =====================
#      PHOTOS API
# =====================

error_reporting(E_ERROR);
ini_set('display_errors', 'On');

require_once("includes/mysqli.php");
require_once("includes/rest.php");

class API extends REST {

	public $data = '';

	private $mysqli = NULL;
	private $cache_dir = 'api-cache';
	private $cache_request = '';


//! Functions
	public function __construct() {
		parent::__construct();
		$this->mysqli = Database();
		//$this->security();
	}

	private function cache($string) {
		IF($this->_request["nocache"] != TRUE) file_put_contents($this->cache_request, $string);
	}

	private function error($error = false) {
		IF(!$error) $this->response('Error code 501, Action not implemented', 501);
		ELSE IF(is_array($error)) $this->response($error[1], $error[0]);
		ELSE $this->response('An Error Occurred', $error);
	}

	private function json($data){
    IF(is_array($data)) {
      IF(!($json = @json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_INVALID_UTF8_SUBSTITUTE))) {
        return json_last_error_msg();
      } ELSE {
        return $json;
      }
    }
  }

	public function requestedAction(){
		$r = explode("/", $_REQUEST['rquest']);
    $func = strtolower(trim(str_replace("/","",$r[0])));
    IF(isset($r[1]) && is_numeric($r[1])) $this->_request["bnb_id"] = $r[1];
    IF(isset($r[2]) && is_numeric($r[2])) $this->_request["number"] = $r[2];

    $pattern = '/([0-9]{1,4})x([0-9]{1,4})/';
    IF(isset($r[2]) && preg_match($pattern, $r[2], $match) || (isset($r[3]) && preg_match($pattern, $r[3], $match))) $this->_request["imagedimensions"] = $match[0];

    //$this->cache_request = $this->cache_dir.'/'.str_replace('--', '-', $func.'-'.strtolower(str_replace(array($func, '--', '/'), '', implode('-', $this->_request)))).'.txt';

    IF((int)method_exists($this,$func) > 0) :
	    IF( filemtime($this->cache_request) < time()-1*(3600 * 24) || $this->_request["nochache"] == TRUE ) : // no cache, or cache expired (24 hours)
		    $this->$func();
		  ELSE :
			  $this->response(file_get_contents($this->cache_request));
		  ENDIF;
    ELSE : $this->error();
    ENDIF;
	}

//! Photo Retrieval Functions
	// EXAMPLE: https://photos.bnbowners.com/get/?bnb_id=ID&imagedimensions=XxY&number=I
	// EXAMPLE: https://photos.bnbowners.com/get/ID/XxY
	private function get() {
		$params = array(
				'limit'            => $this->_request["number"]          ?: 0  ,
				'bnb_id'           => $this->_request["bnb_id"]          ?: FALSE ,
				'imagedimensions'  => $this->_request["imagedimensions"] ?: ''
			);

		IF(!$params["bnb_id"]) { $this->error(array(400, "Error code 400, Required parameters missing")); return; }

		IF(!($stmnt = $this->mysqli->prepare(
					"SELECT
							(@path := 'https://photos.bnbowners.com/') AS path,
							(@filename := REPLACE(bnb.picture, '.jpg', '')) AS filename,
							(@picture := CONCAT(@filename, '.jpg')) AS picture,
							CONCAT(@path, @picture) AS fullpath
						FROM bnb
						WHERE bnb.bnb_id = ?
						LIMIT 1"
			))) : ECHO($this->mysqli->error);
		ELSEIF(!$stmnt->bind_param('i', $params["bnb_id"])) : ECHO($stmnt->error);
		ELSEIF(!$stmnt->execute()) : ECHO($stmnt->error);
		ELSE :
			$result = $stmnt->get_result();
			$row = $result->fetch_object();

			$array = array (
					"dimensions"   => $params["imagedimensions"],
					"filename"     => $row->filename,
					"fullfilename" => $row->picture,
					"path"         => $row->path
				);

      $count = 0;
			$picsdir = "../";
      $files = scandir($picsdir);
      $pattern = '/^('.$row->filename.'\-?([0-9]+))\.jpg$/';
      $photos = array();

      FOREACH($files as $file) :
    		IF(!is_dir($file) && preg_match($pattern, $file, $match)) :
	    		$cropsize = $params["imagedimensions"] != '' ? "_{$params[imagedimensions]}" : '';
	    		$photos[] = array( // (int)$match[2]-1
			    		'filename'         => ($f = "{$match[1]}{$cropsize}.jpg"),
			    		'fullpath'         => "{$row->path}{$f}",
			    		'number'           => (int)$match[2],
			    		'cropped'          => ($params["imagedimensions"] != ''),
			    		'originalfilename' => $file,
			    		'originalpath'     => "{$row->path}{$file}"
			    	);
    		ENDIF;
  		ENDFOREACH;

  		IF(count($photos) == 0) :
    		$file = str_replace('.jpg', '', $row->filename);
    		$full = "{$file}.jpg";
    		IF(!is_dir("{$picsdir}{$full}") && file_exists("{$picsdir}{$full}")) :
	    		$cropsize = $params["imagedimensions"] != '' ? "_{$params[imagedimensions]}" : '';
	    		$photos[0] = array(
			    		'filename'         => ($f = "{$file}{$cropsize}.jpg"),
			    		'fullpath'         => "{$row->path}{$f}",
			    		'number'           => 0,
			    		'cropped'          => ($params["imagedimensions"] != ''),
			    		'originalfilename' => $full,
			    		'originalpath'     => "{$row->path}{$full}"
			    	);
    		ENDIF;
  		ENDIF;

  		ksort($photos);

			$this->response($this->json(array(
				"photos" => $photos,
				"data"   => $array
			)));
		ENDIF;
	}

} // END class API

$api = new API;
$api->requestedAction();
?>