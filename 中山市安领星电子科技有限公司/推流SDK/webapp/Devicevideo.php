<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<?php 
	session_start();
	$_SESSION['username'] = $_GET['username'];
	$_SESSION['access_token'] = $_GET['access_token'];
	$_SESSION['state'] = $_GET['state'];
	$groupid = $_GET['groupid'];
?>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>设备推流</title>
<script src="js/jquery-3.2.1.min.js"></script>
<script src="js/jquery.md5.js"></script>
<script src="js/jquery.ztree.all.js"></script>
<script type="text/javascript" src="sewiseplayer/sewise.player.min.js"></script>
<link rel="stylesheet" href="css/zTreeStyle/zTreeStyle.css" type="text/css">
<script>
var zTreeObj;
var zNodes =[
 			{ id:0, pId:0, name:"我的摄像头", open:true, isParent:true}	
 		];

var m3u8url = null;
var bPlay = true;
var hid = null;
var reconnecTime;
var pushurl = null;

//设备排序,把在线设备排在最前面.
//status为2的表示在线，其他值为不在线

function sortbystatus(a, b)
{
	return (a['status'] > b['status']) ? -1 : 1;
}

var setting = {
		view: {
			dblClickExpand: dblClickExpand,
			selectedMulti: false
		},
		data: {
			simpleData: {
				enable: true
			}
		},
		//异步获取账户下的设备接口
		async: {
			enable: true,
			url:"http://user.hddvs.net:8080/apiv2/GetGroupDeviceAction.php",
			autoParam:["id"],
			dataFilter: filter,
			type:"post",
			otherParam:{"username":"<?php echo $_SESSION['username']; ?>",
						"access_token":"<?php echo $_SESSION['access_token'];?>",
						"state":"<?php echo $_SESSION['state']; ?>"
						}
		},
		
		callback: {
			onDblClick: zTreeOnDblClick,
			onClick:zTreeOnClick
		}
	};

	function dblClickExpand(treeId, treeNode) {
		return treeNode.level > 0;
	}

	//过滤GetGroupDeviceAction.php的接口信息,返回ztree支持json格式
	function filter(treeId, parentNode, childNodes) {
		if (!childNodes) return null;
		  
		  if(childNodes.status == 200)
		  {
			  var jsonres = '[';
			  if(childNodes.groupinfo.length > 0)
			  {
				  for(var i=0; i < childNodes.groupinfo.length; i++)
				  {
					  jsonres += "{ id:'";
					  jsonres +=  childNodes.groupinfo[i]['group_id'] ;
					  jsonres += "', name:'";
					  jsonres += childNodes.groupinfo[i]['group_name'];
					  jsonres += "', isParent:'true'}";
					  jsonres += ",";
				  }
			  }

			  if(childNodes.device_info.length > 0)
			  {	
				  childNodes.device_info.sort(sortbystatus);
				  
				  for(var i=0; i < childNodes.device_info.length; i++)
				  {

					  jsonres += "{ id:'";
					  jsonres += childNodes.device_info[i]['group_id'];
					  jsonres += "', name:'";
					  jsonres += childNodes.device_info[i]['name'];
					  jsonres += childNodes.device_info[i]['hid'];
					  jsonres += "', hid:'";
					  jsonres += childNodes.device_info[i]['hid'];
					  jsonres += "', dataserver:'";
					  jsonres += childNodes.device_info[i]['dataserver'];
					  jsonres += "', isParent:'false'}";
					  jsonres += ",";
					  
				  }
			  }

			  jsonres = jsonres.substr(0, jsonres.length - 1);  
			  jsonres += ']';
		  }
		var arrayres = eval(jsonres);
		return arrayres;
	}
	//解析出IP，将ssp://xxx.xxx.xxx.xxx:5552的IP解析出来
	function parseMediaIPFromUrl(sspurl)
	{
		var ipstr = null;
		var strarray =  new Array();
		strarray = sspurl.split(';');
		for(i=0; i< strarray.length; i++)
		{	
			if(strarray[i].indexOf("ssp://") != -1)
			{
				var iEnd = strarray[i].lastIndexOf(":");
				ipstr = strarray[i].substring(6, iEnd);
				break;
			}
		}
		
		return ipstr;
	}

	function zTreeOnClick(event, treeId, treeNode) {
		if(!treeNode.isParent)
		{	
			setTimeout(function(){
				var sspurl = treeNode.dataserver;
				hid = treeNode.hid;
				var ipurl = parseMediaIPFromUrl(sspurl);
	
				//任意一个可以推流,并输出hls的地址
				pushurl = "rtmp://"+ipurl+"/hls/"+treeNode.hid;
				
				//设备端推流接口，没有时间限制，必须手动关闭推流接口
				$.post("http://user.hddvs.net:8080/apiv2/PushDeviceRtmpAction.php", 
						{
							"username":"<?php echo $_SESSION['username']; ?>",
							"access_token":"<?php echo $_SESSION['access_token'];?>",
							"state":"<?php echo $_SESSION['state']; ?>",
							"deviceid":treeNode.hid,
							"streamid":0,
							"customurl":pushurl
						}, 
	
						function(data, status){
							if(status == "success")
							{
								var obj = jQuery.parseJSON(data);
								if(obj.status == 200)
								{
									m3u8url = "http://"+ipurl+":8091/hls/"+treeNode.hid+".m3u8";
									//var rtmpurl = "rtmp://"+ipurl+"/hls/"+treeNode.hid;
								
									SewisePlayer.toPlay(m3u8url, "我的视频", 0, true);
									bPlay = true;
																		
								}
								else
								{
								}
							}
							else
							{
	
							}
						});
			}, 5000);
		}
	};

	//ztree树节点，双击播放
	function zTreeOnDblClick(event, treeId, treeNode) {

		
			if(!treeNode.isParent)
			{	
				setTimeout(function(){
						var sspurl = treeNode.dataserver;
						hid = treeNode.hid;
						var ipurl = parseMediaIPFromUrl(sspurl);
			
						//任意一个可以推流,并输出hls的地址
						pushurl = "rtmp://"+ipurl+"/hls/"+treeNode.hid;
						
						//设备端推流接口，没有时间限制，必须手动关闭推流接口
						$.post("http://user.hddvs.net:8080/apiv2/PushDeviceRtmpAction.php", 
								{
									"username":"<?php echo $_SESSION['username']; ?>",
									"access_token":"<?php echo $_SESSION['access_token'];?>",
									"state":"<?php echo $_SESSION['state']; ?>",
									"deviceid":treeNode.hid,
									"streamid":0,
									"customurl":pushurl
								}, 
			
								function(data, status){
									if(status == "success")
									{
										var obj = jQuery.parseJSON(data);
										if(obj.status == 200)
										{
											m3u8url = "http://"+ipurl+":8091/hls/"+treeNode.hid+".m3u8";
											//var rtmpurl = "rtmp://"+ipurl+"/hls/"+treeNode.hid;
										
											SewisePlayer.toPlay(m3u8url, "我的视频", 0, true);
											bPlay = true;
																				
										}
										else
										{
										}
									}
									else
									{
			
									}
								});
				}, 5000);
			}
		
	};

	

$(document).ready(function(){
	zTreeObj = $.fn.zTree.init($("#treeDemo"), setting, zNodes);
	//初始化SewisePlayer
	SewisePlayer.setup({
		server: "vod",
		type: "m3u8",
		buffer: 5,
		autostart: "false",
    	skin: "vodFoream",
		lang: "zh_CN",
		draggable: "true",
		claritybutton: "disable",
		videourl: "",
		published:1,
		title: "我的视频"
	}, "player");

	
	//开始播放回调
	SewisePlayer.onStart(function(name){
		
	});
	//视频缓冲回调
	SewisePlayer.onBuffer(function(pt, name){
		clearInterval(reconnecTime);
		bPlay = true;
	});

	SewisePlayer.playerReady(function(id){
		
	});
	
	//视频停止
	SewisePlayer.onStop(function(name){
		
		if(bPlay)
		{
			//重连机制
			//由于推流有时间限制,无法判断是否是由用户手动断开
			//所以需要根据bPlay,来重新推流.
			reconnecTime =setInterval(function(){

				
				$.post("http://user.hddvs.net:8080/apiv2/PushDeviceRtmpAction.php", 
						{
							"username":"<?php echo $_SESSION['username']; ?>",
							"access_token":"<?php echo $_SESSION['access_token'];?>",
							"state":"<?php echo $_SESSION['state']; ?>",
							"deviceid":hid,
							"streamid":0,
							"customurl":pushurl
						}, 

						function(data, status){
							if(status == "success")
							{
								var obj = jQuery.parseJSON(data);
								if(obj.status == 200)
								{
									SewisePlayer.toPlay(m3u8url, "我的视频", 0, true);					
								}
								else
								{
								}
							}
							else
							{

							}
						});
				

				}, 5000);
		
		}
	});
	
	
	$('#leftbtn').click(function(){
		DoControl(1);
	});

	$('#rightbtn').click(function(){
		DoControl(2);	
	});

	$('#topbtn').click(function(){
		DoControl(3);		
	});


	$('#bottombtn').click(function(){
		DoControl(4);
	});

	$('#mirrorbtn').click(function(){
		DoControl(15);
	});

	$('#flipbtn').click(function(){
		DoControl(16);
	});

	$('#zoomin').click(function(){
		DoControl(5);
	});

	$('#zoomout').click(function(){
		DoControl(6);
	});

	$('#focusin').click(function(){
		DoControl(17);
	});

	$('#focusout').click(function(){
		DoControl(18);
	});

	$('#Hcontrol').click(function(){
		DoControl(7);
	});
	
	$('#Vcontrol').click(function(){
		DoControl(8);
	});
	
	$('#Autocontrol').click(function(){
		DoControl(9);
	});
	

	$('#stopcontrol').click(function(){
		DoControl(10);
	});

	//停止播放，并调用停止推流接口
	$('#stopbtn').click(function(){
		bPlay = false;
		SewisePlayer.doStop();
		
		//设备服务器转码推流接口, 停止服务器推流
		$.post("http://user.hddvs.net:8080/apiv2/StopDeviceRtmpAction.php", 
				{
					"username":"<?php echo $_SESSION['username']; ?>",
					"access_token":"<?php echo $_SESSION['access_token'];?>",
					"state":"<?php echo $_SESSION['state']; ?>",
					"deviceid":hid
				}, 

				function(data, status){
					if(status == "success")
					{
						var obj = jQuery.parseJSON(data);
						if(obj.status == 200)
						{
							
						}
						else
						{
						}
					}
					else
					{

					}
				});
		
	});


	$('#setPreform').click(function(){
		var postion = $('#preform_position').val();
		if(postion == "")
			return;
		//预置位设置和调用接口
		$.post("http://user.hddvs.net:8080/apiv2/PreformAction.php", 
				{
					"username":"<?php echo $_SESSION['username']; ?>",
					"access_token":"<?php echo $_SESSION['access_token'];?>",
					"state":"<?php echo $_SESSION['state']; ?>",
					"deviceid":hid,
					"position":postion,
					"set":1
				}, 

				function(data, status){
					if(status == "success")
					{
						var obj = jQuery.parseJSON(data);
						if(obj.status == 200)
						{
							
						}
						else
						{
						}
					}
					else
					{

					}
				});
	});

	$('#callPreform').click(function(){
		var postion = $('#preform_position').val();
		if(postion == "")
			return;
		
		//预置位设置和调用接口
		$.post("http://user.hddvs.net:8080/apiv2/PreformAction.php", 
				{
					"username":"<?php echo $_SESSION['username']; ?>",
					"access_token":"<?php echo $_SESSION['access_token'];?>",
					"state":"<?php echo $_SESSION['state']; ?>",
					"deviceid":hid,
					"position":postion,
					"set":0
				}, 

				function(data, status){
					if(status == "success")
					{
						var obj = jQuery.parseJSON(data);
						if(obj.status == 200)
						{
							
						}
						else
						{
						}
					}
					else
					{

					}
				});
	});


	//云台控制接口
	function DoControl(actioncode)
	{
		$.post("http://user.hddvs.net:8080/apiv2/ControlAction.php", 
				{
					"username":"<?php echo $_SESSION['username']; ?>",
					"access_token":"<?php echo $_SESSION['access_token'];?>",
					"state":"<?php echo $_SESSION['state']; ?>",
					"deviceid":hid,
					"actioncode":actioncode
				}, 

				function(data, status){
					if(status == "success")
					{
						var obj = jQuery.parseJSON(data);
						if(obj.status == 200)
						{
							
						}
						else
						{
						}
					}
					else
					{

					}
				});
			
	}
});

</script>
</head>
<body style="margin:0 auto">
	<center>
	<table border='1' width="850px">
        <tr>
            <td colspan=2><h2>设备推流</h2><br/>用户名:<?php echo $_SESSION['username'];?><br/> 访问token:<?php echo $_SESSION['access_token'];?><br/> 访问state:<?php echo $_SESSION['state'];?></td>
        </tr>
        <tr height="400px">
            <td width="640px" ><div id="player" style="width: 640px;height: 360px;"></div></td>
            <td><ul id="treeDemo" class="ztree"></ul></td>
        </tr>
        <tr>
            <td colspan=2>
            <input type="button" value="左" id="leftbtn"/>
            <input type="button" value="右" id="rightbtn"/>
            <input type="button" value="上" id="topbtn"/>
            <input type="button" value="下" id="bottombtn"/>
            <input type="button" value="停止播放" id="stopbtn"/>
            <input type="button" value="镜像" id="mirrorbtn"/>
            <input type="button" value="翻转" id="flipbtn"/>
            <input type="button" value="拉近" id="zoomin"/>
            <input type="button" value="拉远" id="zoomout"/>
            <input type="button" value="聚焦+" id="focusin"/>
            <input type="button" value="聚焦-" id="focusout"/>
            <input type="button" value="水平扫描" id="Hcontrol"/>
            <input type="button" value="垂直扫描" id="Vcontrol"/>
            <input type="button" value="自动扫描" id="Autocontrol"/>
            <input type="button" value="停止扫描" id="stopcontrol"/>
            <br/>
            <label>预置位:</label>
            <input type="text" id="preform_position"/>
            <input type="button" value="设置预置位" id="setPreform"/>
            <input type="button" value="调用预置位" id="callPreform"/>
            </td>
         
        </tr>
    </table>
        <div>
           <span id="tip"></span>
        </div>
    </center>
</body>
</html>