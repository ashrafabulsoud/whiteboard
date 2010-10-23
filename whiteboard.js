
////////////////////////////////////////////////////////////////////////////////////////////////
// debug function
debug=function(divid,text){
	var d = new Date();
	var min=d.getMinutes();
	var sec=d.getSeconds();
	min=min<10?'0'+min:min;
	sec=sec<10?'0'+sec:sec;
	var date=d.getHours()+":"+min+":"+sec;
	ID=document.getElementById(divid);
	if(ID!=null){
	    var setlength = 3;
	    var old_content = ID.innerHTML;
	    var content = old_content.split("<br>");
	    var length = (content.length>=setlength-1) ? setlength-1 : content.length;
	    var new_content = '';
	    for( i=0; i<length; i++ ){
		new_content += content[i]+"<br>";
	    };
	    ID.innerHTML = date+" > "+text+"<br>"+new_content;
	}else{
	    alert(divid+" not found, text:"+text);
	}
}
////////////////////////////////////////////////////////////////////////////////////////////////
// set draw color - clientside
var drawcolor;
setDrawColor=function(newcolor){
    if(newcolor.length==6 && newcolor.match('[0-9a-fA-F]*')){
	drawcolor=newcolor;
	dc=document.getElementById('drawcolor');
	dc.style.color="#"+drawcolor;
	dc.style.backgroundColor="#"+drawcolor;
    }
}
////////////////////////////////////////////////////////////////////////////////////////////////
// xml-handler - parse the response from the server und draw something
var lastcid = 0;

var CMD = new Array();
CMD['CLEAR']		= 100;
CMD['DRAW_LINE']	= 101;
CMD['DRAW_CIRCLE']	= 102;

xml_handler=function(xml){
		starttime = (new Date()).getTime();
		cmd_count=0;
		lowerlastcid_count=0;
		$(xml).find('command').each(function()
		{
			cmd_count++;
			cid = parseInt($(this).find('cid').text());
			if(cid>lastcid){
				lastcid = cid;
			}else{
				lowerlastcid_count++;
			}
			//Canvas.begin();
			tool = parseInt($(this).find('tool').text());
			switch (tool)
			{
				case CMD['CLEAR']:
					Canvas.clear();
					break;
				case CMD['DRAW_LINE']:
					color = $(this).find('color').text();
					p1x = parseInt($(this).find('p1x').text());
					p1y = parseInt($(this).find('p1y').text());
					p2x = parseInt($(this).find('p2x').text());
					p2y = parseInt($(this).find('p2y').text());
					Canvas.draw_line(color,p1x,p1y,p2x,p2y);
					break;
				case CMD['DRAW_CIRCLE']:
					debug('debug2',"xml-handler: draw_circle is not implemented");
					break;
				default:
					debug('debug2',"xml-handler: received an unknown command");
			}
			//Canvas.end();
		});
		stoptime = (new Date()).getTime();
		difftime=(stoptime-starttime);
		timePerCmd=Math.round(difftime/(cmd_count||1)*100)/100;
		debug('debug2',"xml-handler: "+cmd_count+" commands in "+difftime+" ms (skipping:"+lowerlastcid_count+") "+timePerCmd+" ms/cmd");
		return(cmd_count!=0);
}
////////////////////////////////////////////////////////////////////////////////////////////////
// Command - queue commands to send to the server
var Commands = {
    setBuffer:function(buffer){
	this.buffer=buffer;
    },
    draw_line : function(color,p1x,p1y,p2x,p2y){
	this.buffer.push('draw_line|'+color+','+p1x+','+p1y+','+p2x+','+p2y+';');
    },
    clear : function(){
	this.buffer.push('clear;');
    }
};
////////////////////////////////////////////////////////////////////////////////////////////////
// Canvas - to actually draw something
Canvas={
    setCanvas:function(canvasid,width,height){
	this.canvas=document.getElementById(canvasid);
	this.context=this.canvas.getContext('2d');
	//c.width=document.width;
	this.canvas.width  = width;
	this.canvas.height = height;
    },
    draw_line:function(color,p1x,p1y,p2x,p2y){
	this.context.strokeStyle='#'+color+'';
//	this.context.lineCap='round';
//	this.context.lineWidth=4;
	this.context.beginPath();
	this.context.moveTo(p1x,p1y);
	this.context.lineTo(p2x+.1,p2y);
	this.context.stroke();
    },
    clear:function(){
	// paper background - white
	this.context.fillStyle='#ffffff';
	this.context.fillRect(0,0,this.canvas.width,this.canvas.height);
    }
};
////////////////////////////////////////////////////////////////////////////////////////////////
// mouse-handler
mousemovehandler=function(e){
    if(e.button!=undefined){
	stopx = e.clientX-Canvas.canvas.offsetLeft;
	stopy = e.clientY-Canvas.canvas.offsetTop;
	diff=5;
	dx = stopx-startx;
	dy = stopy-starty;
	if( (dx*dx+dy*dy) >= (diff*diff) ){
		//debug('debug',"("+startx+","+starty+")("+stopx+","+stopy+") "+(dx*dx+dy*dy));
		Commands.draw_line(drawcolor,startx,starty,stopx,stopy);
		Canvas.draw_line(drawcolor,startx,starty,stopx,stopy);
		startx=stopx;
		starty=stopy;
	}
    }else{
	mouseuphandler();
    }
};
mousedownhandler=function(e){
    startx=e.clientX-Canvas.canvas.offsetLeft;
    starty=e.clientY-Canvas.canvas.offsetTop;
    document.addEventListener('mousemove',mousemovehandler,1);
};
mouseuphandler=function(){
    document.removeEventListener('mousemove',mousemovehandler,1);
};
////////////////////////////////////////////////////////////////////////////////////////////////
$(document).ready(function(){
	// initialize drawcolor - clientside
	setDrawColor('000000');
	// initialize Canvas
	Canvas.setCanvas('canvas',800,400);
	// register mouse up/down-handler
	Canvas.canvas.addEventListener('mousedown',mousedownhandler,0);
	document.addEventListener('mouseup',mouseuphandler,0);

	toSend = new Array();
	Commands.setBuffer(toSend);

	function get_param(){
		var xml='';
		while(true){
		    var element = toSend.pop();
		    if(element!=null){
			xml += element;
		    }else{
			break;
		    }
		}
		//debug('debug3',"xml: "+(xml.split(';').length-1)+" commands send");
		return {
			'lastcid':lastcid,
			'xml':xml
		};
	}
	$.PeriodicalUpdater({
		method: 'POST',
		url: 'ajax.php',
		sendDatafunc: get_param,
		type: 'xml'
	},
	function(xml) {
		xml_handler(xml);
	});
});
////////////////////////////////////////////////////////////////////////////////////////////////
