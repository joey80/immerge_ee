var express = require('express'),
    serveStatic = require('serve-static'),
    helmet = require('helmet'),
    app = express(),
    port = process.env.PORT || 5000;

app.use(helmet());
app.use(serveStatic(__dirname, { index: 'index.html' }));

app.listen(port);

console.log('server started ' + port);