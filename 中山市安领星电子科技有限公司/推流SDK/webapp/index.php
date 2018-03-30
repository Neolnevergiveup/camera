<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>视频demo</title>
<script src="js/jquery-3.2.1.min.js"></script>
<script src="js/jquery.md5.js"></script>
<script>
function uuid(len, radix) {
	  var chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.split('');
	  var uuid = [], i;
	  radix = radix || chars.length;
	 
	  if (len) 
	  {
	  	for (i = 0; i < len; i++) 
		  	uuid[i] = chars[0 | Math.random()*radix];
	  } 
	  else 
	  {
	   var r;
	 
	   uuid[8] = uuid[13] = uuid[18] = uuid[23] = '-';
	   uuid[14] = '4';

	   for (i = 0; i < 36; i++) 
	   {
	    if (!uuid[i]) 
		{
	     r = 0 | Math.random()*16;
	     uuid[i] = chars[(i == 19) ? (r & 0x3) | 0x8 : r];
	    }
	   }
	  }
	 
	  return uuid.join('');
	}
	
	$(document).ready(function(){
		
		$("#randomcodebtn").click(function(){
			var code = uuid(16, 16);
			$("#randomcodetxt").val(code);
		});

		$('#loginbtn').click(function(){
			if($('#username').val() == '' || $('#password').val() == '' || $('#randomcodetxt').val() == '')
			{
				$('#tip').html('用户名,密码，和随机码不能为空!');
			}
			else
			{
				$('#tip').html('');
			}

			//设备登录接口
			$.post("http://user.hddvs.net:8080/apiv2/LoginAction.php",{
			    username:$('#username').val(),
			    password: $.md5($('#password').val()),
				state:$('#randomcodetxt').val()
			  }, function(data, status){
				  if(status == "success")
				  {
					  $('#tip').html("Data: " + data + "<br />Status: " + status);
					  var obj = jQuery.parseJSON(data);
					  if(obj.status == 200)
					  {

						  var pushtype = $("#pushtype").val();
						  if(pushtype == "1")
						  {
							  location.href="Cloudvideo.php?username="+$('#username').val()+"&access_token="+obj.access_token+"&state="+$('#randomcodetxt').val();
						  }
						  else
						  {
							  location.href="Devicevideo.php?username="+$('#username').val()+"&access_token="+obj.access_token+"&state="+$('#randomcodetxt').val();
						  }
					    
					  }
					  else
					  {
						  $('#tip').html("login failed!");
					  }
				  }
				  else
				  {
					  $('#tip').html("login failed!");
				  }
				
			});
		});
		
	});

	
</script>
</head>
<body style="margin:0 auto">
	<center>
	<table>
        <tr>
            <td colspan="3" style="text-align:center"><h2>用户登录</h2></td>
        </tr>
        <tr>
            <td>用户名</td>
            <td colspan="2"><input type="text" value="" style="border:1px solid #b4b4b4;width:180px" id="username"/></td>
        </tr>
        <tr>
            <td>密码</td>
            <td colspan="2"><input type="password" value="" style="border:1px solid #b4b4b4;width:180px" id="password"/></td>
        </tr>
        <tr>
            <td>访问随机码</td>
            <td><input type="text" value="" style="border:1px solid #b4b4b4;width:180px" id="randomcodetxt"/></td>
            <td><input type="button" value="生成随机码" id="randomcodebtn"/></td>
        </tr>
         <tr>
            <td>推流方式:</td>
            <td colspan="2">
            	<select id="pushtype">
				  <option value ="1">服务器转码推流</option>
				  <option value ="2"  selected = "selected">摄像头推流</option>
				</select>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="text-align:center"><br /><input type="button" value="登录" id="loginbtn"/></td>
        </tr>
    </table>
        <div>
           <span id="tip"></span>
        </div>
    </center>
</body>
</html>