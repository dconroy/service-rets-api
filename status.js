var express = require('express'),
    app = express();

var healthcheck = require('docker-healthcheck').create( {
  "statuses": {
    "elastic_status": false
  }
} );

app.set( 'port', 3000 );

app.get('/', function( req, res ) {

  //console.log(require('util').inspect( req.headers, {showHidden: false, depth: 10, colors: true}));

  healthcheck.get( req, res, function() {
    healthcheck.send();
  } );

} );

app.listen(app.get('port'), function () {
  console.log('Status Service Listening On Port: ' + app.get('port'));
});