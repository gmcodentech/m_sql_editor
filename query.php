<?php

$coun=0;

	function get_connection(){
		return mysqli_connect('localhost', 'root', '1234');
	}

    function db_get_database_list()
    {

        $dbs=array();
        $link = get_connection();
        if(!$link)
        {
            die('server not connected');
        }

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
        return $dbs;
    }
    function db_execute_query($query,$db_name)
    {
    	global $coun;
        $link = get_connection();
        if(!$link)
        {
            die('server not connected');
        }

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
         if($result)
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
                $table='<span>Query Executed Successfully. No of rows affected is: </span>'. mysqli_affected_rows();
            }


         }
mysqli_close($link);
           return $table;
     }

?>
<?php

    function get_select_str()
    {
		try{
        $select_str='';
        $db=db_get_database_list();

        for($i=0;$i<count($db);$i++)
        {
            $d=$db[$i];
            if(isset($_POST['db']) && $d->Database == $_POST['db'])
            {
                $select_str.='<option value="'.$d->Database.'" selected>'.$d->Database.'</option>';
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
	try{
    if(isset($_POST['exec']) && $_POST['exec'] == 'Execute')
    {
         if(strlen($_POST['query'])!=0)
         {
          $query=$_POST['qry'];
          $query_str=$_POST['query'];
          $db_name=$_POST['db'];
          //echo $db_name;
          $table_str=db_execute_query($query,$db_name);
         }
         else
         {
            $msg='query empty';
         }
    }
	}
	catch(Exception $e){
			$msg=$e->getMessage();
	}

?>

<!DOCTYPE html>
<html>
    <head>
        <title>M-SQL Editor</title>
		
		<link rel="stylesheet" href="querystyle.css">
        <script language="javascript" type="text/javascript">
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
        <SPAN style="font-size:22px;font-weight:bold;">M-SQL Editor</span> <span style="font-size:14px;color:Green;font-weight:bold;">Mysql/Mariadb Query Editor</span><br/><hr/>
        <?php
        $this_page=$_SERVER['PHP_SELF'];

        echo('
        <form action="'.$this_page.'" method="post">
        <lablel>DB:</label><select name="db">'.get_select_str().'</select><input type="submit" id="exec" name="exec" value="Execute" onclick="javascript:get_text();"/><br/>
        <span style="font-weight:bold;">TYPE QUERY:</span><br/>
        <textarea  name="query" id="query" onkeydown="handleTabKey(event, this);" />'.$query_str.'</textarea><br/>
        <input type="hidden" id="qry" name="qry"/>
        </form>
<hr/>

           <span style="font-weight:bold;">Result:</span>&nbsp;'.$msg.'
		   <span style="font-weight:bold;color:#C34100">Total Records:'.$coun.'</span>
		   <div style="overflow:scroll;margin:0px;padding:0px;width:100%px;height:300px;">
           '.$table_str.'
		   </div>');
           ?>
        </div>

        <div>


    </body>
</html>
