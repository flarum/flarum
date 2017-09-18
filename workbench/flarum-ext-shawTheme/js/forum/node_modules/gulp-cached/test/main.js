var cache = require('../');
var should = require('should');
var File = require('vinyl');
var through = require('through2');
var PassThrough = require('stream').PassThrough;
require('mocha');

describe('gulp-cached', function() {
  it('should create a cache that only allows a file through once', function(done) {
    var file = new File({
      path: "/home/file.js",
      contents: new Buffer("damn")
    });
    var stream = cache('test');
    stream.on('data', function(nfile){
      nfile.path.should.equal(file.path);
      nfile.contents.toString().should.equal(file.contents.toString());
      done();
    });
    stream.write(file);
    stream.write(file);
    stream.write(file);
    stream.write(file);
    stream.write(file);
  });

  it('should create a cache that clears content when reset', function(done) {
    var file = new File({
      path: "/home/file.js",
      contents: new Buffer("damn")
    });
    var file2 = new File({
      path: "/home/file.js",
      contents: new Buffer("damnit")
    });
    var stream = cache('testing');
    var count = 0;
    stream.on('data', function(nfile){
      count++;
      nfile.path.should.equal(file.path);
    });
    stream.on('end', function(){
      count.should.equal(5);
      done();
    });

    // 1
    stream.write(file);
    stream.write(file);

    // 2
    stream.write(file2);
    stream.write(file2);

    // 3
    stream.write(file);
    stream.write(file);

    // 4
    stream.write(file2);
    stream.write(file2);

    // 5
    stream.write(file);
    stream.write(file);

    stream.end();
  });


  it('should create separate caches that only allow a file through once each', function(done) {
    var file = new File({
      path: "/home/file.js",
      contents: new Buffer("damn")
    });
    var stream = cache('testio');
    var stream2 = cache('testio2');
    var count = 0;
    var count2 = 0;

    stream.on('data', function(nfile){
      count++;
      nfile.path.should.equal(file.path);
      nfile.contents.toString().should.equal(file.contents.toString());
    });
    stream2.on('data', function(nfile){
      count2++;
      nfile.path.should.equal(file.path);
      nfile.contents.toString().should.equal(file.contents.toString());
    });

    stream.on('end', function(){
      count.should.equal(1, 'count');
      stream2.end();
    });
    stream2.on('end', function(){
      count2.should.equal(1, 'count2');
      done();
    });

    stream.write(file);
    stream.write(file);
    stream2.write(file);
    stream2.write(file);

    stream.end();
  });

  it('should create a cache that allows a stream file through always', function(done) {
    var file = new File({
      path: "/home/file.js",
      contents: through()
    });
    var stream = cache('testilo');
    var count = 0;
    stream.on('data', function(nfile){
      count++;
      nfile.path.should.equal(file.path);
    });
    stream.on('end', function(){
      count.should.equal(5);
      done();
    });
    stream.write(file);
    stream.write(file);
    stream.write(file);
    stream.write(file);
    stream.write(file);
    stream.end();
  });

  it('should create a cache that allows a streaming file through always', function(done) {
    var file = new File({
      path: "/home/file.js",
      contents: new PassThrough()
    });
    var stream = cache('testoyo');
    var count = 0;
    stream.on('data', function(nfile){
      count++;
      nfile.path.should.equal(file.path);
    });
    stream.on('end', function(){
      count.should.equal(5);
      done();
    });
    stream.write(file);
    stream.write(file);
    stream.write(file);
    stream.write(file);
    stream.write(file);
    stream.end();
  });

  it('should create a cache that only allows a hashed streaming file through once', function(done) {
    var file = new File({
      path: "/home/file.js",
      contents: new PassThrough()
    });
    file.checksum = 'deadbeef';
    var stream = cache('testyeah');
    var count = 0;
    stream.on('data', function(nfile){
      count++;
      nfile.path.should.equal(file.path);
    });
    stream.on('end', function(){
      count.should.equal(1);
      done();
    });
    stream.write(file);
    stream.write(file);
    stream.write(file);
    stream.write(file);
    stream.write(file);
    stream.end();
  });
});
