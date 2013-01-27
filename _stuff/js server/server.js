var port = 8080;

// include modules
var express = require('express');

// create server instance
var app = express();

app.use(express.logger('dev'));
app.use(express.static('public'));

app.use(express.basicAuth(function(user, pass, fn){
	User.authenticate({
		user: user,
		pass: pass
	}, fn);
}));

app.get('/', function(req, res){
	res.send('Hello World');
});

// start listning at chosen port
app.listen(port);

// node console output
console.log('Server running at %s', port);