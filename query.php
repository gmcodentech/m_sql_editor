<?php
session_start();
$coun=0;
$show_login = !(isset($_SESSION['logged_in']) && $_SESSION['logged_in']==true) ?? true;
$login_error = '';
$host='localhost';
$user='';
$pwd='';
function get_connection(){
	
	$conn = mysqli_connect($_SESSION['host'], $_SESSION['user'], $_SESSION['pass']);
	if(!$conn)
	{
		die('server not connected');
	}
	return $conn;
}

function db_get_database_list()
{

	$dbs=array();
	
	$link = get_connection();
	
	$query="show databases";

	$db_result=mysqli_query($link,$query);
	if($db_result)
	{
		while ($row = mysqli_fetch_object($db_result))
		{
		$dbs[]=$row;
		}
	}

	mysqli_close($link);
	//}
	return $dbs;
}
function db_execute_query($query,$db_name)
{
	global $coun;
	$link = get_connection();

	$db=mysqli_select_db($link,$db_name);

	if(!$db)
	{
		die('database not connected');
	}
	
	$result=mysqli_query($link,$query);

	$table='';
	if($e=mysqli_error($link)!='')
	{
	$table.='<span style="color:red;">'.mysql_error().'</span>';
	}
	if($result!=false)
	{
		
		if(mysqli_affected_rows($link) > 0 && !is_bool($result))
		{
		$table.='<table>';
		$table.='<thead>';
		for($i=0;$i<mysqli_num_fields($result);$i++)
		{
		   $meta = mysqli_fetch_field($result);
		   $table.='<th>'.$meta->name.'</th>';

		}
		$table.='</thead>';
		$coun=0;
		while($row=mysqli_fetch_array($result))
		{
			$table.='<tr>';
			for($i=0;$i<mysqli_num_fields($result);$i++)
			{
				if(!is_null($row[$i])==0)
				{
					$row[$i]='null';
				}

				$table.='<td >'.$row[$i].'</td>';
			}
			$table.='</tr>';

			$coun++;
		}
		$table.='</table>';
		}
		else
		{
			$table='<span>Query Executed Successfully. No of rows affected is: </span>'. mysqli_affected_rows($link);
		}


	 }
	mysqli_close($link);
	return $table;
 }

?>
<?php

function get_select_str($dbname)
{
	try{
	$select_str='';
	$db=db_get_database_list();

	for($i=0;$i<count($db);$i++)
	{
		$d=$db[$i];
		if(trim(strtolower($d->Database)) == trim(strtolower($dbname))){
			$_POST['db']=trim(strtolower($dbname));
		}

		if(isset($_POST['db']) && $d->Database == $_POST['db'])
		{
			$select_str.='<option value="'.$d->Database.'" selected="selected">'.$d->Database.'</option>';
		}
		else
		{
			$select_str.='<option value="'.$d->Database.'">'.$d->Database.'</option>';
		}
	}
	return $select_str;
	}
	catch(Exception $e){
			die($e->getMessage());
	}
}
?>
<?php
$table_str='';
$query_str='';
$msg='';
$ediHeight='80';
$is_error=false;
$sel_db = '';

if(isset($_POST['login_btn']) && $_POST['login_btn'] == 'Connect'){
	
	$success=false;
	
	$host=$_POST['hostname'];
	$user=$_POST['username'];
	$pass=$_POST['password'];
	try{
		$conn = mysqli_connect($host, $user, $pass);
		if(!$conn)
		{
			$success=false;
		}
		else{
			mysqli_select_db($conn,'mysql');
			$success = mysqli_query($conn,"select count(1) as result from user where host='{$host}' and user='{$user}' and password=password('{$pass}');")
				->fetch_object()
				->result == 1;
			$show_login=false;
			$_SESSION['logged_in']=true;
		}
	}
	catch(Exception $e){
		$login_error=$e->getMessage();
	}
	
	if(!$success){
		$login_error='cannot connect to server';
	}else{
		$_SESSION['user']=$user;
		$_SESSION['host']=$host;
		$_SESSION['pass']=$pass;
		$show_login=false;
	}
}

if(isset($_POST['logout']) && $_POST['logout']=='Disconnect'){
	session_destroy();
	$show_login=true;
	$_SESSION['logged_in']=false;
}

try{
if(isset($_POST['exec']) && $_POST['exec'] == 'Execute')
{
	 $ediHeight=$_POST['ediHt'];
	 if(strlen($_POST['query'])!=0)
	 {
	  $query=$_POST['qry'];
	  $query_str=$_POST['query'];
	  $sel_db = check_for_use_db($query);
	  $db_name=$_POST['db'];
	  $table_str=db_execute_query($query,$db_name);
	 }
	 else
	 {
		$msg='query empty';
	 }
}
}
catch(Exception $e){
		$is_error=true;
		$msg=$e->getMessage();
}



function check_for_use_db($query){
	$words = preg_split('/\s+/',$query);
	if(count($words)==2 && strtolower(trim($words[0]))=='use'){
		return trim($words[1]);
	}
	
	return '';
}



?>

<!DOCTYPE html>
<html>
<head>
	<title>M-SQL Editor</title>
	
	<link rel="stylesheet" href="querystyle.css">
	<script language="javascript" type="text/javascript">
		function updateHeight() {
            var textarea = document.getElementById("query");
            var ediHt = document.getElementById("ediHt");
            var height = textarea.offsetHeight;
            ediHt.value = height;
        }

        window.onload = function() {
            var textarea = document.getElementById("query");
            var resizeObserver = new ResizeObserver(updateHeight);
            resizeObserver.observe(textarea);
            updateHeight();
        };
		
		function get_text()
		{
			 var e = document.getElementById('query');
			 var q = document.getElementById('qry');
			 var l = e.selectionEnd - e.selectionStart;
			 if(e.value.substr(e.selectionStart, l).length>0)
			 {
				 q.value =e.value.substr(e.selectionStart, l);

			 }
			 else
			 {
				  q.value = e.value;
			 }

			 return true;
		}
		
		function handleTabKey(event, element) {
if (event.keyCode === 9) {
	var v = element.value,
		s = element.selectionStart,
		e = element.selectionEnd;
	element.value = v.substring(0, s) + '\t' + v.substring(e);
	element.selectionStart = element.selectionEnd = s + 1;
	event.preventDefault(); // Prevent the default tab action
	return false;
}
return true;
}
	</script>
</head>
<body>
	<div id="wrapper">
	<?php
	$this_page=$_SERVER['PHP_SELF'];
	if($show_login){
	$login_html = '<div id="login"><form action="'.$this_page.'" method="post">';
	$login_html .= '<span style="margin-left:10px;">Host: </span><input type="input" name="hostname" value="'.$host.'"/>';
	$login_html .= '<span style="margin-left:10px;">Username: </span><input type="input" name="username" value="'.$user.'"/>';
	$login_html .= '<span style="margin-left:10px;">Password: </span><input name="password" type="password" value="'.$pwd.'"/>';
	$login_html .= '<input type="submit" value="Connect" class="btn" id="login_btn" name="login_btn"/>';
	$login_html .= '<div style="margin-left:10px;" id="login_error">'.$login_error.'</div>';
	$login_html .= '</form></div>';
	echo $login_html;
	}
	?>
	<div>
	<form action="<?php echo $this_page; ?>" method="post">
	<SPAN style="font-size:22px;font-weight:bold;">M-SQL Editor</span> <span style="font-size:14px;color:teal;font-weight:bold;font-size:1em;">Mysql/Mariadb Query Editor</span>
	<?php
	if(!$show_login){
		echo '<span style="float:right;padding-right:3px;"><input type="submit" value="Disconnect" name="logout" class="btn"/></span><br/>';
	}
	?>
	</form>
	</div>
	<hr/>
	<?php
	
	if(!$show_login){

	$html = '
	<form action="'.$this_page.'" method="post">
	<lablel>Database:</label><select name="db" id="db_list">'.get_select_str($sel_db).'</select><input type="submit" id="exec" name="exec" value="Execute" onclick="javascript:get_text();"/><br/>
	<div style="margin:4px 0px;">Type query in the following box</div>
	<textarea  name="query" id="query" onkeydown="handleTabKey(event, this);" style="height:'.$ediHeight.'px;" />'.$query_str.'</textarea>
	<input type="hidden" id="qry" name="qry"/>
	<input type="hidden" id="ediHt" name="ediHt"/>
	</form>
	<hr/>

   <div style="font-weight:bold;padding:2px;">';
   
   if($is_error){
	$html.='<span style="color:#f00;">Error:'.$msg.'</span>';
   }else{
	$html.='<span>Success,</span>';
   }
   if(!$is_error){
	$html.='<span style="font-weight:bold;">Total Records : '.$coun.'</span>';
   }
   $html.='</div>
   <div style="overflow:scroll;margin:0px;padding:0px;width:auto;height:450px;">
   '.$table_str.'
   </div>';
	   
   echo $html;
   }
	   ?>
	</div>
</body>
</html>
