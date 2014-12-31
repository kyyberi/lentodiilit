<?php
// require 'phpmailer/class.phpmailer.php';

include_once 'epi/Epi.php';
Epi::setPath('base', 'epi');
Epi::setSetting('exceptions', true);
Epi::init('route');
Epi::init('api');
Epi::init('base','cache','session');
//Epi::init('base','cache-apc','session-apc');
//Epi::init('base','cache-memcached','session-apc');

/*
 * This is a sample page whch uses EpiCode.
 * There is a .htaccess file which uses mod_rewrite to redirect all requests to index.php while preserving GET parameters.
 * The $_['routes'] array defines all uris which are handled by EpiCode.
 * EpiCode traverses back along the path until it finds a matching page.
 *  i.e. If the uri is /foo/bar and only 'foo' is defined then it will execute that route's action.
 * It is highly recommended to define a default route of '' for the home page or root of the site (yoursite.com/).
 */
//$router = new EpiRoute();

// GET METHODS
getRoute()->get('/', array('Api', 'info'));

getRoute()->get('/deals', array('Api', 'deals'));
getRoute()->get('/deals/recent', array('Api', 'recent'));

getRoute()->get('/categories', array('Api', 'categories'));


getRoute()->get('.*', array('ApiErrors', 'error404'));

getRoute()->run(); 


/*
 * ******************************************************************************************
 * Define functions and classes which are executed by EpiCode based on the $_['routes'] array
 * ******************************************************************************************
 */
class Api
{

  public function info()
  {
$docpage = file_get_contents('doc.html');
echo $docpage;
  }

  public function info2()
  {
    echo "diilit() , palauttaa listan diileista";
  }

  public function recent()
  {
    echo "recent() , returns list of most recent deals";
  }


  public function contains($str, $arr)
  {
    foreach($arr as $a) {
        if (stripos($str,$a) !== false) return true;
    }
    return false;
  }


  static public function deals()
  {
	$con = Api::getConnection();
	if (mysqli_connect_errno($con))
  	{
 		$errno = mysqli_connect_errno();
		$reason = mysqli_connect_error();
		ApiErrors::errorDbConnection($reason, $errno);
 	} else {

	    	$tiedot = "{ \"deals\": [\n";
		$title = "";
		$acount = "";
		$deals = mysqli_query($con,"select postid, url,title,published, categories, tags, price FROM deal order by published desc;") 
			 or die(ApiErrors::errorDbQuery(mysqli_error($con), mysqli_errno($con), __FUNCTION__));	
	 	$num_rows = mysqli_num_rows($deals);
		while ($row = mysqli_fetch_array($deals)):  
			$postid = $row['postid'];
			$url = $row['url'];
			$title = $row[utf8_encode('title')];
			$price = $row['price'];
			$tags    =   $row['tags'];
			$categories    =   $row['categories'];
			$published = $row['published'];

			$tiedot .= "    {\"postid\":\"".$postid."\",";
			$tiedot .= "\n     \"url\": \"".$url."\",";
			$tiedot .= "\n     \"title\": \"".$title."\",";
			$tiedot .= "\n     \"categories\": \"".$categories."\",";
			$tiedot .= "\n     \"tags\": \"".$tags."\",";
			$tiedot .= "\n     \"published\": \"".$published."\",";
			$tiedot .= "\n     \"price\": \"".$price."\"\n     },\n";
		endwhile; 
		$cout = substr($tiedot, 0, strlen($tiedot) -2);
		$cout .="],\n";
	 	// lisää rights osuus
		$rights = Api::getRights();
		$cout .= $rights;
		$cout .= "\n}";
		// palauta JSON headerilla
		Api::outputJSON($cout);


		// lisää lokiin tieto
		// Api::addToLog($con);
		$con->close();
	}
  
  }


  static public function categories()
  {
        $con = Api::getConnection();
        if (mysqli_connect_errno($con))
        {
                $errno = mysqli_connect_errno();
                $reason = mysqli_connect_error();
                ApiErrors::errorDbConnection($reason, $errno);
        } else {

                $tiedot = "{ \"categories\": [\n";
                $title = "";
                $acount = "";
                $deals = mysqli_query($con,"select DISTINCT categories from deal") 
                         or die(ApiErrors::errorDbQuery(mysqli_error($con), mysqli_errno($con), __FUNCTION__)); 
                $num_rows = mysqli_num_rows($deals);
                while ($row = mysqli_fetch_array($deals)):  

			$category    =   $row['categories'];
                        $tiedot .= "\n     {\"name\": \"".$category."\"\n     },\n";
                endwhile; 
                $cout = substr($tiedot, 0, strlen($tiedot) -2);
                $cout .="],\n";
                // lisää rights osuus
                $rights = Api::getRights();
                $cout .= $rights;
                $cout .= "\n}";
                // palauta JSON headerilla
                Api::outputJSON($cout);

                // lisää lokiin tieto
                // Api::addToLog($con);
                $con->close();
        }
  
  }




  public function addToLog($conn){
		$table_columns = api::getLogColNames();
		$val = api::getConnDetails('GET');
		$query = "INSERT INTO api (".$table_columns.") VALUES(".$val.");";
		$insert = mysqli_query($conn,$query) or die(mysql_error());
  }


  public function normalize_str($str){
	$invalid = array(' '=>'-', 'Š'=>'S','?'=>'','!'=>'', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z',
	'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A',
	'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E',
	'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
	'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y',
	'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a',
	'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e',  'ë'=>'e', 'ì'=>'i', 'í'=>'i',
	'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
	'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y',  'ý'=>'y', 'þ'=>'b',
	'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r', "`" => "'", "´" => "'", "„" => ",", "`" => "'",
	"´" => "'", "“" => "\"", "”" => "\"", "´" => "'", "&acirc;€™" => "'", "{" => "",
	"~" => "", "–" => "-", "’" => "'", "\"" => "");
	 
	$str = str_replace(array_keys($invalid), array_values($invalid), $str); 
	return $str;
  }


  public function outputJSON($content)
  {
  
	header("HTTP/1.1 200 OK");

// Allow from any origin
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Credentials: true');
	header('Access-Control-Expose-Headers: Origin, Expires, Content-Type, Content-Language, Access-Control-Allow-Origin');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
	header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    }

    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        header("Access-Control-Allow-Headers: *");
    }
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-Type: application/json; charset=utf-8');
	header('Access-Token: 124321421345546dfgdgshfg345345');
	header('Link: <http://creativecommons.org/licenses/by-sa/3.0/>; rel="license"');
	echo $content;

  }
  public function myreplace($str){
	$invalid = array('"'=>' ');
	$str = str_replace(array_keys($invalid), array_values($invalid), $str);
	 
	return $str;
  }

  public function getLogColNames() 
   {
	$vars = "api_method, api_name, api_referer, api_ip_address, api_user_agent";
        return $vars;
   } 

  public function getConnDetails($method, $name) 
   {
	
	$ret = "";
	$ret .="'".$method."',";
	$ret .="'".$_SERVER['REQUEST_URI']."',";
	$ret .="'".$_SERVER['HTTP_REFERER']."',";
	$ret .="'".$_SERVER['REMOTE_ADDR']."',";
	$ret .="'".$_SERVER['HTTP_USER_AGENT']."'";
	return $ret;
   }	

 public function getConnection(){
	$con = mysqli_connect("myhost","myuser","mypassw","mybd")
	return $con;
 }

 public function showRights(){
        $ret .=" {\"rights\": [{";
        $ret .="\n    \"contentLicense\": \"http://creativecommons.org/licenses/by-sa/3.0/\",";
        $ret .="\n    \"dataLicense\": \"http://creativecommons.org/licenses/by-sa/3.0/\",";
        $ret .="\n    \"copyrightNotice\": \"copyright lentodiilit.fi ".date('Y')."\",";
        $ret .="\n    \"attributionText\": \"Sisältö on lentodiilit.fi tuottamaa.\",";
        $ret .="\n    \"attributionURL\": \"http://lentodiilit.fi\"";
        $ret .="\n }]";
	$ret .= "}";
	// palauta JSON headerilla
	Api::outputJSON($ret);

 }

 public function getRights(){
	$ret = "\n \"rights\": [{";
        $ret .="\n    \"contentLicense\": \"http://creativecommons.org/licenses/by-sa/3.0/\",";
        $ret .="\n    \"dataLicense\": \"http://creativecommons.org/licenses/by-sa/3.0/\",";
        $ret .="\n    \"copyrightNotice\": \"copyright lentodiilit.fi ".date('Y')."\",";
        $ret .="\n    \"attributionText\": \"Sisältö on lentodiilit.fi tuottamaa.\",";
        $ret .="\n    \"attributionURL\": \"http://lentodiilit.fi\"";
        $ret .="\n }]";
	return $ret;
 }



}

/* ########## ApiErrors Class ################ */

class ApiErrors
{
  static public function error404() {
	$uri = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$tiedot = "{ \"errors\": [\n";
	$tiedot .= "     {\"code\" : 404, \n";
	$tiedot .= "      \"reason\" : \"Page Does Not Exist\", \n";
	$tiedot .= "      \"uri\" : \"".$uri."\" \n";
        $tiedot .= "     }";
	$tiedot .= "\n]}"; 
	header('HTTP/1.1 404 Not Found');
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-Type: application/json; charset=utf-8');
	echo $tiedot;
  }

  static public function errorEmpty() {
	$uri = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$tiedot = "{ \"errors\": [\n";
	$tiedot .= "     {\"code\" : 204, \n";
	$tiedot .= "      \"reason\" : \"No Content\", \n";
	$tiedot .= "      \"uri\" : \"".$uri."\" \n";
        $tiedot .= "     }";
	$tiedot .= "\n]}"; 
	header('HTTP/1.1 204 No Content');
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-Type: application/json; charset=utf-8');
	echo $tiedot;
  }

  static public function errorDbConnection($reason, $errno) {
	$uri = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$tiedot = "{ \"errors\": [\n";
	$tiedot .= "     {\"code\" : ".$errno.", \n";
	$tiedot .= "      \"reason\" : \"".$reason."\", \n";
	$tiedot .= "      \"uri\" : \"".$uri."\" \n";
        $tiedot .= "     }";
	$tiedot .= "\n]}"; 
	header('HTTP/1.1 500 Internal Server Error');
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-Type: application/json; charset=utf-8');
	echo $tiedot;
  }
  static public function errorDbQuery($reason, $errno, $met) {
	$uri = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	$tiedot = "{ \"errors\": [\n";
	$tiedot .= "     {\"code\" : ".$errno.", \n";
	$tiedot .= "      \"reason\" : \"".$reason."\", \n";
	$tiedot .= "      \"method\" : \"".$met."\", \n";
	$tiedot .= "      \"uri\" : \"".$uri."\" \n";
        $tiedot .= "     }";
	$tiedot .= "\n]}"; 
	header('HTTP/1.1 500 Internal Server Error');
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-Type: application/json; charset=utf-8');
	echo $tiedot;
  }



}

