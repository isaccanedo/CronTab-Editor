<?php

// +-------------------------+
// | @author Isac Canedo     |
// +-------------------------+

class Cron
{
	public $path = "";
	public $line = array();
	public $object = array();
	public function __construct($path =  NULL)
	{
		if($path !== NULL)
		{
			$this->path = $path;
			$this->parse();
		}
	}
	public function parse($path =  NULL)
	{
		if($path !== NULL)
		{
			$this->path = $path;
		}
		
		$valid_cron = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '*');
		$buff = file_get_contents($this->path);
		$buff = str_replace("\n", "\r\n", $buff);
		$buff = str_replace("\r\r\n", "\r\n", $buff);
		$this->line = explode("\r\n", $buff);
		
		foreach($this->line as $num=>$line)
		{
			$line = trim($line);
			$this->object[$num] = array('valid'=>false, 'line'=>$line, 'data'=>array());
			if(strlen($line) > 0)
			{
				// validation
				$first_char = substr($line, 0, 1);
				if(in_array($first_char, $valid_cron))
				{
					// remove double space
					$line = preg_replace('/\s+/', ' ', $line);
					$arr = explode(' ', $line, 7);
					if(count($arr) > 5)
					{
						$minute  = $arr[0];
						$hour    = $arr[1];
						$date     = $arr[2];
						$month   = $arr[3];
						$day_of_week     = $arr[4];
						$user    = $arr[5];
						$command = $arr[6];
						
						$this->object[$num] = array('valid'=>true, 'line'=>$line, 'data'=>array(
							'minute'=>$minute,
							'hour'=>$hour,
							'date'=>$date,
							'month'=>$month,
							'day_of_week'=>$day_of_week,
							'user'=>$user,
							'command'=>$command
						));
					}
				}
			}
		}		
	}
	public function add($line)
	{
		$cronData = array();
		if(is_array($line))
		{
			$cronData = $line;
		}
		else
		{
			$line = preg_replace('/\s+/', ' ', $line);
			$arr = explode(' ', $line, 7);
			if(count($arr) > 5)
			{
				$minute      = $arr[0];
				$hour        = $arr[1];
				$date        = $arr[2];
				$month       = $arr[3];
				$day_of_week = $arr[4];
				$user        = $arr[5];
				$command     = $arr[6];
				$cronData = array(
					'minute'=>$minute,
					'hour'=>$hour,
					'date'=>$date,
					'month'=>$month,
					'day_of_week'=>$day_of_week,
					'user'=>$user,
					'command'=>$command);
			}
		}
		$this->object[count($this->object)] = array('valid'=>true, 'line'=>$line, 'data'=>$cronData);
	}
	public function render()
	{
		$text = "";
		foreach($this->object as $num=>$object)
		{
			$text .= $object['line']."\r\n";
		}
		return $text;
	}
	public function inList($data, $collection)
	{
		foreach($collection as $key=>$data2)
		{
			if(
				$data['minute'] == $data2['minute']
				&& $data['hour'] == $data2['hour']
				&& $data['date'] == $data2['date']
				&& $data['month'] == $data2['month']
				&& $data['day_of_week'] == $data2['day_of_week']
				&& $data['user'] == $data2['user']
				&& $data['command'] == $data2['command']
				)
			{
				return true;
			}
		}
		return false;
	}
	public function compareRemove($referenceData)
	{
		$temp = $this->object;
		$temp2 = $this->object;
		$ref = $referenceData;
		if(count($referenceData) > 0)
		{
			foreach($temp as $idx=>$obj)
			{
				if($obj['valid'])
				{
					// only modify if valid
					if($this->inList($obj['data'], $referenceData))
					{
					}
					else
					{
						// remove 
						$temp2[$idx] = NULL;
					}
				}
			}
			// construct
			$temp = array();
			foreach($temp2 as $idx=>$val)
			{
				if($val !== NULL)
				{
					$temp[] = $val;
				}
			}
		}
		else
		{
			foreach($temp as $idx=>$obj)
			{
				if($obj['valid'])
				{
					$temp2[$idx] = NULL;
				}
			}
			$temp = array();
			foreach($temp2 as $idx=>$val)
			{
				if($val !== NULL)
				{
					$temp[] = $val;
				}
			}
		}
		// update
		$this->object = $temp;
	}
	public function clear()
	{
		$temp = $this->object;
		$temp2 = $this->object;
		foreach($temp as $idx=>$obj)
		{
			if($obj['valid'])
			{
				$temp2[$idx] = NULL;
			}
		}
		$temp = array();
		foreach($temp2 as $idx=>$val)
		{
			if($val !== NULL)
			{
				$temp[] = $val;
			}
		}
		$this->object = $temp;
	}
	public function compareAdd($referenceData)
	{
		$data = array();
		foreach($this->object as $obj)
		{
			if($obj['valid'])
			{
				$data[] = $obj['data'];
			}
		}
		foreach($referenceData as $idx=>$obj)
		{
			if($this->inList($obj, $data))
			{
			}
			else
			{
				// add 
				$data[] = $obj;
				$line = $obj['minute'].' '.$obj['hour'].' '.$obj['date'].' '.$obj['month'].' '.$obj['day_of_week'].' '.$obj['user'].' '.$obj['command'];
				$this->object[count($this->object)] = array('valid'=>true, 'line'=>$line, 'data'=>$obj);
			}
		}
	}
	public function writeToFile()
	{
		$content = $this->render();
		$content = str_replace("\r\n\r\n\r\n", "\r\n\r\n", $content);
		$content = str_replace("\r\n\r\n\r\n", "\r\n\r\n", $content);
		$content = trim($content, "\r\n");
		file_put_contents($this->path, $content);
	}
	
}


$cronFile = "/etc/crontab";
$cronFile = dirname(__FILE__)."/crontab.txt";
$cron = new Cron($cronFile);

if(isset($_POST['update']))
{
	if(isset($_POST['idx']))
	{
		$idx = $_POST['idx'];
		if(is_array($idx))
		{
			$referenceData = array();			

			foreach($idx as $index)
			{
				$referenceData[] = array(
					'minute'      => @$_POST['minute_'.$index], 
					'hour'        => @$_POST['hour_'.$index], 
					'date'        => @$_POST['date_'.$index], 
					'month'       => @$_POST['month_'.$index], 
					'day_of_week' => @$_POST['day_of_week_'.$index], 
					'user'        => @$_POST['user_'.$index], 
					'command'     => @$_POST['command_'.$index]
					);
			}
			$cron->compareAdd($referenceData);
			$cron->compareRemove($referenceData);
			$render = $cron->writeToFile();
		}
	}
	else
	{
		$cron->clear();
		$render = $cron->writeToFile();		
	}
}


?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Crontab Editor</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.2/jquery.min.js"></script>
<style type="text/css">
body{
	margin:0;
	padding:0;
	position:relative;
}
.content{
	width:100%;
	padding:20px;
	box-sizing:border-box;
}
</style>

<script type="text/javascript">

	var template = ''
	+'<tr>\r\n'
	+'	<td><a href="#" class="cron-remover"><i class="fa fa-times"></i></a>\r\n'
	+'	<td><input type="hidden" name="idx[]" class="idx" value="" /><input type="text" required="required" class="form-control" data-name="minute" value="" /></td>\r\n'
	+'	<td><input type="text" required="required" class="form-control" data-name="hour" value="" /></td>\r\n'
	+'	<td><input type="text" required="required" class="form-control" data-name="date" value="" /></td>\r\n'
	+'	<td><input type="text" required="required" class="form-control" data-name="month" value="" /></td>\r\n'
	+'	<td><input type="text" required="required" class="form-control" data-name="day_of_week" value="" /></td>\r\n'
	+'	<td><input type="text" required="required" class="form-control" data-name="user" value="" /></td>\r\n'
	+'	<td><input type="text" required="required" class="form-control" data-name="command" value="" /></td>\r\n'
	+'</tr>\r\n';
	
	$(document).ready(function(e) {
        $(document).on('click', '.cron-remover', function(e){
			$(this).closest('tr').remove();
			e.preventDefault();		
		});
        $(document).on('click', '#add', function(){
			addRow();		
		});
    });
function addRow()
{
	$('#cron-table').find('tbody').append(template);
	$('#cron-table').find('tbody').find('tr').each(function(index, element) {
        var tr = $(this);
		tr.find('input.idx').val(index);
				
		tr.find('input[type="text"]').each(function(index2, element2) {
            $(this).attr('name', $(this).attr('data-name')+'_'+index);
        });
    });
}
</script>

</head>

<body>
<div class="content">
<form action="" method="post" enctype="application/x-www-form-urlencoded">
<table class="table table-bordered" width="100%" id="cron-table">
	<thead>
    	<tr>
        	<td width="20"><i class="fa fa-times"></i></td>
        	<td width="11%">Minute</td>
        	<td width="11%">Hour</td>
        	<td width="11%">Date</td>
        	<td width="11%">Month</td>
        	<td width="11%">Day of Week</td>
        	<td width="11%">User</td>
        	<td>Command</td>
        </tr>
    </thead>
	<tbody>
    	<?php
		foreach($cron->object as $idx=>$object)
		{
			if($object['valid'])
			{
			$cronData = $object['data'];
			$minute=$cronData['minute'];
			$hour=$cronData['hour'];
			$date=$cronData['date'];
			$month=$cronData['month'];
			$day_of_week=$cronData['day_of_week'];
			$user=$cronData['user'];
			$command=$cronData['command'];
		?>
    	<tr>
        	<td><a href="#" class="cron-remover"><i class="fa fa-times"></i></a></td>
        	<td><input type="hidden" name="idx[]" class="idx" value="<?php echo $idx;?>" />
                <input type="text" required="required" class="form-control" data-name="minute" name="minute_<?php echo $idx;?>" value="<?php echo $minute;?>" /></td>
        	<td><input type="text" required="required" class="form-control" data-name="hour" name="hour_<?php echo $idx;?>" value="<?php echo $hour;?>" /></td>
        	<td><input type="text" required="required" class="form-control" data-name="date" name="date_<?php echo $idx;?>" value="<?php echo $date;?>" /></td>
        	<td><input type="text" required="required" class="form-control" data-name="month" name="month_<?php echo $idx;?>" value="<?php echo $month;?>" /></td>
        	<td><input type="text" required="required" class="form-control" data-name="day_of_week" name="day_of_week_<?php echo $idx;?>" value="<?php echo $day_of_week;?>" /></td>
        	<td><input type="text" required="required" class="form-control" data-name="user" name="user_<?php echo $idx;?>" value="<?php echo $user;?>" /></td>
        	<td><input type="text" required="required" class="form-control" data-name="command" name="command_<?php echo $idx;?>" value="<?php echo $command;?>" /></td>
        </tr>
        <?php
			}
		}
		?>
    </tbody>
</table>
<div>
	<input type="button" id="add" class="btn btn-primary" value="Add">
	<input type="submit" name="update" class="btn btn-success" value="Update">
</div>
</form>
</div>
</body>
</html>
