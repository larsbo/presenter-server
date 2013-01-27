//----------------------------------------------------------------
// This short JavaScript program is 100% pure JS and uses no external
// libraries.  It will show you how to use a WebSocket properly to
// connect to TouchEventServer.exe and begin getting TUIO data to
// your browser apps.  It draws a square around each touch event
// from your TUIO multitouch system.  Must run full screen.
//
// Copyright 2013 DougX.net
// use freely with citation for noncomercial purposes
//----------------------------------------------------------------

   // web socket
   var g_webSocket = null;
   var g_websocket_host;
   var g_websocket_port;
   var g_socket_interval;

   // for drawing on the screen (canvas 2d context)
   var g_frame_rate;
   var g_canvas;
   var g_context;
   var g_render_interval;

   // some flags
   var g_focus;
   var g_tuio_connected;

   // this holds all the touch events currently being tracked
   var g_cursors = new Array();

   // this is the object type held in the g_cursors array
   function Cursor( id, ix, iy )
   {
      this.myX = ix;
      this.myY = iy;
      this.myId = id;
   }

   Cursor.prototype.render = function()
   {
      g_context.strokeStyle = "white";
      g_context.strokeRect( this.myX - 20, this.myY - 20, 40, 40 );
   }

   // establish main load event
   document.addEventListener('DOMContentLoaded', function () {
   document.body.onload = loadEvent;
   });

//----------------------------------------------------------------
// EXECUTION STARTS HERE:
// loadEvent is where execution begins when the document is loaded
//----------------------------------------------------------------
function loadEvent()
{
   g_websocket_host = "localhost";
   g_websocket_port = "3334";
   g_frame_rate = 60;

   g_focus = true;
   g_tuio_connected = false;
   
   g_canvas = document.getElementById('gameCanvas');

   if (!g_canvas.getContext)
   {
      dbg("Fatal error, can't get 2d context.");
      return;
   }
   g_context = g_canvas.getContext('2d');

   window.onresize = resizeCanvas;
   resizeCanvas();

   // divorce from the body load event
   setTimeout(main, 100);
}

function main()
{
   window.addEventListener('focus', windowFocus);
   window.addEventListener('blur', windowBlur);

   g_canvas.addEventListener('mousedown', ev_mousedown, false);
   g_canvas.addEventListener('mouseup', ev_mouseup, false);
   g_canvas.addEventListener('mousemove', ev_mousemove, false);

   // everything event driven is now set up, so establish the two
   // intervals that drive the rest of the program:  the socket connection
   // check, and the render loop
   g_render_interval = setInterval(renderLoop, 1000/g_frame_rate);
   g_socket_interval = setInterval(socketCheck, 1000);
}

//----------------------------------------------------------------
// When data comes in over the WebSocket, it gets parsed and these
// callbacks are invoked depending on the contents of the data.
//----------------------------------------------------------------
function touchAdd(id, x, y)
{
   g_cursors.push( new Cursor(id,x,y) );
}

function touchUpdate(id, x, y)
{
   for ( var i = 0; i < g_cursors.length; ++i )
   {
      if ( g_cursors[i].myId == id )
      {
         g_cursors[i].myX = x;
         g_cursors[i].myY = y;
      }
   }
}

function touchRemove(id)
{
   var freshCursors = new Array();
   for ( var i = 0; i < g_cursors.length; ++i )
   {
      if ( g_cursors[i].myId != id )
      {
         freshCursors.push( g_cursors[i] );
      }
   }
   g_cursors = null;
   g_cursors = freshCursors;
}

//----------------------------------------------------------------
// Check the status of the WebSocket. If the socket is not connected
// and the app has focus, connect the web socket. This function is
// on a one second timer, set up earlier in main()
//----------------------------------------------------------------
function socketCheck()
{
   if ((g_webSocket == null ) &&  g_focus )
   {
      var wsloc = "ws://" + g_websocket_host + ":" + g_websocket_port;
      g_webSocket = new WebSocket(wsloc);

      g_webSocket.onopen = sockopen;
      g_webSocket.onclose = sockclose;
      g_webSocket.onmessage = sockmsg;
      g_webSocket.onerror = sockerr;
   }
}

function sockopen(evt)
{
   g_tuio_connected = true;
   dbg("Web socket connected.");
}

function sockclose(evt)
{
   dbg("Web socket disconnected.",1);
   g_webSocket = null;
   g_tuio_connected = false;
}

function sockerr(evt)
{
   dbg("Web socket error, killing connection.",2);
   g_webSocket = null;
   g_tuio_connected = false;
}

function sockmsg(evt)
{
   // incoming message is ID,OPERATION,X,Y
   // where operation is A,R,U  for add,remove,update
   var xyarr = evt.data.split(",");

   var id = xyarr[0];
   var op = xyarr[1];

   var x = parseFloat(xyarr[2]);
   var y = parseFloat(xyarr[3]);

   x *= g_canvas.width;
   y *= g_canvas.height;

   if ( op == "A" )
      touchAdd(id, Math.round(x), Math.round(y));
   else if ( op == "R" )
      touchRemove(id);
   else if ( op == "U" )
      touchUpdate(id, Math.round(x), Math.round(y));
}

//----------------------------------------------------------------
// When the app loses focus (blur event) we need to relinquish the
// web socket connection.  This makes sure we don't hog the system
// TUIO resources when the user isn't even using this app.  Closing
// the WebSocket disconnects from TouchEventServer.exe causes it to
// free up the TUIO connection.
//
// Likewise when we regain focus, reconnect the WebSocket. This will
// cause TouchEventServer to reconnect to the TUIO driver. Note that
// when we regain focus we just set a flag to say so.  The one second
// socketCheck() timer will actually reconnect the socket.
//----------------------------------------------------------------
function windowFocus()
{
   g_focus = true;
}

function windowBlur()
{
   g_focus = false;
   if ( g_webSocket != null )
   {
      g_webSocket.close();
      g_webSocket = null;
   }
}


//----------------------------------------------------------------
// Draw the canvas, this is set up on a timer for a 60 frames per
// second redraw.
//----------------------------------------------------------------
function renderLoop()
{
   g_context.font = "14px Arial";

   g_context.fillStyle = "black";
   g_context.fillRect(0,0, g_canvas.width, g_canvas.height);

   var lineSpace = 20;
   g_context.fillStyle = "yellow";

   var text = "screen size: " + screen.width + "," + screen.height;
   g_context.fillText(text, 0, lineSpace);
   text = "press backspace when finished";
   g_context.fillText(text, 800, lineSpace);

   lineSpace += 20;
   var text = "window size: " + g_canvas.width + "," + g_canvas.height;
   g_context.fillText(text, 0, lineSpace);

   if ((screen.width != g_canvas.width ) || ( screen.height != g_canvas.height))
   {
      g_context.fillStyle = "red";
      text = "APPLICATION MUST BE RUN FULL SCREEN";
      g_context.fillText(text, 200, lineSpace-20);
      text = "PRESS  F11  TO MAKE YOUR BROWSER FULL SCREEN";
      g_context.fillText(text, 200, lineSpace);
   }
   else
   {
      g_context.fillStyle = "green";
      text = "FULL SCREEN IS DETECTED";
      g_context.fillText(text, 200, lineSpace-20);
   }

   g_context.fillStyle = "yellow";
   lineSpace += 20;
   lineSpace += 20;
   text = "websocket host: " + g_websocket_host;
   g_context.fillText(text, 0, lineSpace);

   lineSpace += 20;
   text = "websocket port: " + g_websocket_port;
   g_context.fillText(text, 0, lineSpace);

   if ( g_tuio_connected )
   {
      g_context.fillStyle = "green";
      text = "WEB SOCKET IS CONNECTED";
      g_context.fillText(text, 200, lineSpace-20);
   }
   else
   {
      g_context.fillStyle = "red";
      text = "WEB SOCKET IS NOT CONNECTED";
      g_context.fillText(text, 200, lineSpace-20);
   }

   g_context.fillStyle = "white";
   lineSpace += 40;
   text = "THERE ARE " + g_cursors.length + " TOUCH EVENTS DETECTED";
   g_context.fillText(text, 0, lineSpace);
   lineSpace += 20;

   for ( var i = 0; i < g_cursors.length; ++i )
   {
      lineSpace += 20;
      text = g_cursors[i].myId + ": " +
             g_cursors[i].myX + "," + 
             g_cursors[i].myY;
      g_context.fillText(text, 0, lineSpace);

      // draw box around each touch
      g_cursors[i].render();
   }
}


//----------------------------------------------------------------
// The window has been resized, so adjust the size of the canvas
// to match the size of the window.
//----------------------------------------------------------------
function resizeCanvas(e)
{
   var w = window.innerWidth;
   var h = window.innerHeight;

   if ( w < 640 )
      w = 640;
   if ( h < 480 )
      h = 480;

   g_canvas.width = w;
   g_canvas.height = h;

}

//--------------------------------------------------------------------
// Handle mouse events (just treat them like they were another touch
// event with the ID of "MOUSE" instead of a number) 
//--------------------------------------------------------------------
function ev_mousedown(ev)
{
   var x = ev.offsetX;
   var y = ev.offsetY;

   touchAdd("MOUSE",x,y);
}

function ev_mouseup(ev)
{
   touchRemove("MOUSE");
}

function ev_mousemove(ev)
{
   if ( ev.which != 1 )
   {
      return;
   }

   var x = ev.offsetX;
   var y = ev.offsetY;

   if ( this.x != x || this.y != y )
      touchUpdate("MOUSE", x, y);

   this.x = x;
   this.y = y;
}

//----------------------------------------------------------------
// a convenience function for writing to the console at different
// importance levels.
//----------------------------------------------------------------
   function dbg(msg, level)
   {
      if (level == undefined || level == null)
         console.log(msg);
      else if (level == 1)
         console.warn(msg);
      else
         console.error(msg);
   }

