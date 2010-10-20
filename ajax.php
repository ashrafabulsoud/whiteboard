<?php

require_once('config.php');
require_once('whiteboard.php');
require_once('debugfile.php');

class Ajax{
	function __construct(){
		$this->clear();
	}

	function clear(){
		$this->msg="";
		$this->msgcount=0;
	}

	function add($cid='0',$tool='',$color='000000',$p1x='0',$p1y='0',$p2x='0',$p2y='0'){
		$this->msg .= "<command>\n";
		$this->msg .= "<cid>"   . $cid   . "</cid>\n";
		$this->msg .= "<tool>"  . $tool  . "</tool>\n";
		$this->msg .= "<color>" . $color . "</color>\n";
		$this->msg .= "<p1x>"   . $p1x   . "</p1x>\n";
		$this->msg .= "<p1y>"   . $p1y   . "</p1y>\n";
		$this->msg .= "<p2x>"   . $p2x   . "</p2x>\n";
		$this->msg .= "<p2y>"   . $p2y   . "</p2y>\n";
		$this->msg .= "</command>\n";
		$this->msgcount++;
	}

	function write(){
		debug("Ajax:write(): send ".$this->msgcount." messages");
		// client ajax call handler
		header("content-type: text/xml");
		echo "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n\n";
		echo "<commandlist>\n" . $this->msg . "</commandlist>\n";
		$this->clear();
	}
}

$wbobj = new Whiteboard($wbdbfn);
$ajax = new Ajax();

$xml 	= array_key_exists('xml', $_POST) ? $_POST['xml'] 	: null;
// $xml="clear;draw_line|000000,100,100,200,200;draw_line|000000,200,200,400,300;"
$xml_commands=explode(';',$xml);
debug("Ajax: receive ".(count($xml_commands)-1)." commands");

foreach($xml_commands as $command){
  if($command!=""){
    $cmd_part=explode('|',$command);
    $cmd_params=explode(',',$cmd_part[1]);
    switch($cmd_part[0]){
      case 'draw_line': if(count($cmd_params)==5){$wbobj->cmd_draw_line($cmd_params[0],$cmd_params[1],$cmd_params[2],$cmd_params[3],$cmd_params[4]);}else{debug("Ajax: draw_line, wrong count");};break;
      case 'clear'    : if(count($cmd_params)==1){$wbobj->cmd_clear();}else{debug("Ajax: clear, wrong count");};break;
      default         : debug("Ajax: unknown command");break;
    }
  }
}

// do an update
$lastcid = array_key_exists('lastcid', $_POST) ? $_POST['lastcid'] 	: 0;
foreach($wbobj->get_updates($lastcid) as $action){
	$ajax->add($action['cid'],$action['tool'],$action['color'],$action['point1x'],$action['point1y'],$action['point2x'],$action['point2y']);
}

$ajax->write();
// close DB
$wbobj->db_close();
exit

?>
