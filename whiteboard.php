<?php

require_once('debugfile.php');

$CMD = array();
$CMD['NULL']		= '0';
$CMD['CLEAR']		= '100';
$CMD['DRAW_LINE']	= '101';
$CMD['DRAW_CIRCLE']	= '102';

//PDO::setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,true);


class Whiteboard {
	public $dbfn = null;
	public $dbh = null;

	function __construct($dbfn) {
		$this->dbfn = $dbfn;
		$this->db_open();
	}

	// DB functions
	function db_open() {
		try{
			$this->dbh = new PDO('sqlite:' . $this->dbfn);
		}catch(PDOExecption $e){
			echo "db_open:".$e->getMessage();
		}
	}

	function db_close() {
		$this->dbh  = null;
	}	

	function db_create() {
		try{
			$r = $this->dbh->exec(
			    "CREATE TABLE command (cid BIGINT PRIMARY KEY ASC, tool INTEGER, color VARCHAR(6), point1x INTEGER, point1y INTEGER, point2x INTEGER, point2y INTEGER)"
			);
		}catch(PDOExecption $e){
			echo "db_create:".$e->getMessage();
		}
	}

	function db_delete() {
		unlink($this->dbfn);
	}

	// helperfunctions
	function get_cid() {
		list($msec, $sec) = explode(" ", microtime());
		return (sprintf("%02d%04d", $sec - 1286400000, floor($msec * 10000)));
	}

	// update functions
	function get_updates($lastcid){
		$sth = $this->dbh->prepare("SELECT cid, tool, color, point1x, point1y, point2x, point2y FROM command WHERE cid > ?");
		$sth->execute(array($lastcid));
		$result = array();
		$resultcount=0;
		foreach ($sth->fetchAll() as $action) {
			$result[] =  $action;
			$resultcount++;
		}
		debug("get_updates(): reported ".$resultcount." results");
		return $result;
	}
	// command functions
	function cmd_draw_line($color,$p1x,$p1y,$p2x,$p2y){
		try{
		    global $CMD;
		    $cid = $this->get_cid();
		    $sth = $this->dbh->prepare("INSERT INTO command VALUES (?, ?, ?, ?, ?, ?, ?)");
		    $r = $sth->execute(array($cid, $CMD['DRAW_LINE'], $color, $p1x, $p1y, $p2x, $p2y));
		    if (!$r){
			$errormsg="INSERT command 'draw_line' failed [cid ".$cid."](".$color.','.$p1x.','.$p1y.','.$p2x.','.$p2y.")";
			print "cmd_draw_line:".$errormsg."\n";
			print_r($sth->errorInfo());
			$msg='';
			foreach($sth->errorInfo() as $info){
			    $msg.="|".$info;
			}
			debug("ERROR: cmd_draw_line: reported ".$errormsg." |#| ".$msg);

		    }
		}catch(PDOExecption $e){
			print "cmd_draw_line:".$e->getMessage();
			debug("Exception: cmd_draw_line: reported ".$e->getMessage());
		}
	}

	function cmd_clear(){
		$sth = $this->dbh->prepare("DELETE FROM command");
		$r = $sth->execute();
		if (!$r){
			print "cmd_clear :: DELETE command failed\n";
			print_r($sth->errorInfo());
		}
		// insert a clear cmd
		global $CMD;
		$cid = $this->get_cid();
		$sth = $this->dbh->prepare("INSERT INTO command VALUES (?, ?, null, null, null, null, null)");
		$r = $sth->execute(array($cid, $CMD['CLEAR']));
		if (!$r)
			print "INSERT command failed\n";
	}
}
?>