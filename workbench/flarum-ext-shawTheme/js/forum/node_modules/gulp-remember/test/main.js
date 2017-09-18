'use strict';

var remember = require('../'),
    should = require('should'),
    sinon = require('sinon'),
    util = require('gulp-util'),
    File = util.File;

function makeTestFile(path, contents) {
  contents = contents || 'test file';
  return new File({
    path: path,
    contents: new Buffer(contents)
  });
}

require('mocha');

describe('gulp-remember', function () {
  describe('remember', function () {
    it('should pass one file through a previously empty cache', function (done) {
      var stream = remember('passOneThrough'),
          file = makeTestFile('./fixture/file.js', 'just a file'),
          filesSeen = 0;
      stream.on('data', function (file) {
        file.path.should.equal('./fixture/file.js');
        file.contents.toString().should.equal('just a file');
        filesSeen++;
      });
      stream.once('end', function () {
        filesSeen.should.equal(1);
        done();
      });
      stream.write(file);
      stream.end();
    });

    it('should pass many files through a previously empty cache', function (done) {
      var i,
          stream = remember('manyFilesThrough'),
          filesSeen = 0;

      stream.on('data', function (file) {
        file.path.should.startWith('./fixture/');
        file.path.should.endWith('.js');
        file.contents.toString().should.startWith('file');
        filesSeen++;
      });
      stream.once('end', function () {
        filesSeen.should.equal(100);
        done();
      });
      for (i = 0; i < 100; i++) {
        stream.write(makeTestFile('./fixture/' + i + '.js', 'file ' + i));
      }
      stream.end();
    });

    it('should remember the files passed through it on subsequent uses', function (done) {
      var stream = remember('remember'),
          anotherStream,
          filesSeen = 0,
          startingFiles = [makeTestFile('./fixture/one'), makeTestFile('./fixture/two')],
          oneMoreFile = makeTestFile('./fixture/three');

      stream.resume(); // don't care about reading the files on this first go-round
      stream.once('end', function () {
        // done writing for the first time. write another file, then check we have three in total.
        anotherStream = remember('remember');
        anotherStream.on('data', function () {
          filesSeen++;
        });
        anotherStream.once('end', function () {
          filesSeen.should.equal(3);
          done();
        });
        anotherStream.write(oneMoreFile);
        anotherStream.end();
      });
      startingFiles.forEach(function (file) {
        stream.write(file);
      });
      stream.end();
    });

    it('should disregard file path case', function (done) {
      var stream = remember('case-test'),
          anotherStream,
          filesSeen = 0,
          contentsSeen = '',
          file1 = makeTestFile('./fixture/ONE', 'ONE'),
          file2 = makeTestFile('./fixture/one', 'one');

      stream.resume(); // don't care about reading the files on this first go-round
      stream.once('end', function () {
        anotherStream = remember('case-test');
        anotherStream.on('data', function (f) {
          contentsSeen += f.contents.toString();
          filesSeen++;
        });
        anotherStream.once('end', function () {
          filesSeen.should.equal(1);
          contentsSeen.should.equal('one');
          done();
        });
        anotherStream.write(file2);
        anotherStream.end();
      });
      stream.write(file1);
      stream.end();
    });

    it('should use the default cache when no name is passed', function (done) {
      var stream = remember(),
          anotherStream,
          filesSeen = 0,
          i;

      stream.resume(); // don't care about reading the files on this first go-round
      stream.once('end', function () {
        anotherStream = remember();
        anotherStream.on('data', function () {
          filesSeen++;
        });
        anotherStream.on('end', function () {
          filesSeen.should.equal(2000);
          done();
        });
        for (i = 1000; i < 2000; i++) {
          anotherStream.write(makeTestFile('./fixture/' + i + '.js'));
        }
        anotherStream.end();
      });
      for (i = 0; i < 1000; i++) {
        stream.write(makeTestFile('./fixture/' + i + '.js'));
      }
      stream.end();
    });

    it('should not pass duplicates through', function (done) {
      var stream = remember('noDuplicates'),
          anotherStream,
          filesSeen = 0;

      stream.resume();
      stream.once('end', function () {
        anotherStream = remember('noDuplicates');
        anotherStream.on('data', function () {
          filesSeen++;
        });
        anotherStream.on('end', function () {
          filesSeen.should.equal(6);
          done();
        });
        anotherStream.write(makeTestFile('./fixture/three'));
        anotherStream.write(makeTestFile('./fixture/four'));
        anotherStream.write(makeTestFile('./fixture/five'));
        anotherStream.write(makeTestFile('./fixture/six'));
        anotherStream.end();
      });
      stream.write(makeTestFile('./fixture/one'));
      stream.write(makeTestFile('./fixture/two'));
      stream.write(makeTestFile('./fixture/three'));
      stream.write(makeTestFile('./fixture/four'));
      stream.end();
    });
  });

  describe('forget', function () {
    it('should forget a file it used to know', function (done) {
      var stream = remember('forget'),
          anotherStream,
          filesSeen = 0;
      stream.resume();
      stream.once('end', function () {
        remember.forget('forget', './fixture/one');
        anotherStream = remember('forget');
        anotherStream.on('data', function (file) {
          file.path.should.equal('./fixture/two');
          filesSeen++;
        });
        anotherStream.on('end', function () {
          filesSeen.should.equal(1);
          done();
        });
        anotherStream.write(makeTestFile('./fixture/two'));
        anotherStream.end();
      });
      stream.write(makeTestFile('./fixture/one'));
      stream.end();
    });

    it('should not throw when target cache does not exist', function () {
      (function () {
        remember.forget('peaceAndLove', 'some/file');
      }).should.not.throw();
    });

    it('should not throw when target cache exists but file does not', function () {
      remember('kittens');
      (function () {
        remember.forget('kittens', 'Mister_McButtercups');
      }).should.not.throw();
    });

    it('should log a warning when target cache does not exist', function () {
      var logStub = sinon.stub(util, 'log'),
          logArgs;
      remember.forget('peaceAndLove', 'some/file');
      logStub.called.should.be.true;
      logArgs = logStub.args[0];
      // Should append the name of the plugin
      logArgs[0].should.equal('gulp-remember');
      // Should warn about the specific cache name
      logArgs[1].should.containEql('peaceAndLove');

      logStub.restore();
    });

    it('should log a warning when target files does not exist in target cache', function () {
      var logStub = sinon.stub(util, 'log'),
          logArgs;
      remember('cacheThatExists');
      remember.forget('cacheThatExists', 'file/that/doesnt/exist');
      logStub.called.should.be.true;
      logArgs = logStub.args[0];
      // Should append the name of the plugin
      logArgs[0].should.equal('gulp-remember');
      // Should warn about the specific cache name
      logArgs[1].should.containEql('file/that/doesnt/exist');

      logStub.restore();
    });
  });

  describe('forgetAll', function () {
    it('should forget all files in a populated cache', function (done) {
      var stream = remember('forgetAll'),
          anotherStream,
          filesSeen = 0;
      stream.resume();
      stream.once('end', function () {
        remember.forgetAll('forgetAll');
        anotherStream = remember('forgetAll');
        anotherStream.on('data', function (file) {
          file.path.should.equal('./fixture/three');
          filesSeen++;
        });
        anotherStream.on('end', function () {
          filesSeen.should.equal(1);
          done();
        });
        anotherStream.write(makeTestFile('./fixture/three'));
        anotherStream.end();
      });
      stream.write(makeTestFile('./fixture/one'));
      stream.write(makeTestFile('./fixture/two'));
      stream.end();
    });

    it('should forget all files in the default cache', function (done) {
      var stream = remember(),
          anotherStream,
          filesSeen = 0;
      stream.resume();
      stream.once('end', function () {
        remember.forgetAll();
        anotherStream = remember();
        anotherStream.on('data', function (file) {
          file.path.should.equal('./fixture/three');
          filesSeen++;
        });
        anotherStream.on('end', function () {
          filesSeen.should.equal(1);
          done();
        });
        anotherStream.write(makeTestFile('./fixture/three'));
        anotherStream.end();
      });
      stream.write(makeTestFile('./fixture/one'));
      stream.write(makeTestFile('./fixture/two'));
      stream.end();
    });

    it('should not throw when target cache does not exist', function () {
      (function () {
        remember.forgetAll('peanutButterJellyTime');
      }).should.not.throw();
    });

    it('should log a warning when target cache does not exist', function () {
      var logStub = sinon.stub(util, 'log'),
          logArgs;
      remember.forgetAll('peanutButterJellyTime');
      logStub.called.should.be.true;
      logArgs = logStub.args[0];
      // Should append the name of the plugin
      logArgs[0].should.equal('gulp-remember');
      // Should warn about the specific cache name
      logArgs[1].should.containEql('peanutButterJellyTime');

      logStub.restore();
    });

    it('should not throw on subsequent forgetAll calls', function (done) {
      var stream = remember('forgetAllMulti');
      stream.resume();
      stream.once('end', function () {
        remember.forgetAll('forgetAllMulti');
        (function () {
          remember.forgetAll('forgetAllMulti');
        }).should.not.throw();
        done();
      });
      stream.write(makeTestFile('./what/ever'));
      stream.end();
    });
  });

  describe('cacheFor', function () {
    it('should return the named cache', function (done) {
      var stream = remember('cacheFor'),
          cache,
          file;
      stream.resume();
      stream.once('end', function () {
        cache = remember.cacheFor('cacheFor');
        cache.should.be.an.instanceOf(Object);
        file = cache['whitehouse/nuclear-codes.rtf'];
        file.should.be.an.instanceOf(Object);
        file.should.have.property('path', 'whitehouse/nuclear-codes.rtf');
        done();
      });
      stream.write(makeTestFile('whitehouse/nuclear-codes.rtf'));
      stream.end();
    });

    it('should return the default cache', function (done) {
      var stream = remember(),
          cache,
          file;
      stream.resume();
      stream.once('end', function () {
        cache = remember.cacheFor();
        cache.should.be.an.instanceOf(Object);
        file = cache['jennifer-lawrence/fully-clothed.jpg'];
        file.should.be.an.instanceOf(Object);
        file.should.have.property('path', 'jennifer-lawrence/fully-clothed.jpg');
        done();
      });
      stream.write(makeTestFile('jennifer-lawrence/fully-clothed.jpg'));
      stream.end();
    });

    it('should return nothing if given bogus cache name', function () {
      var cache = remember.cacheFor('speculation-on-the-guilt-or-innocence-of-adnan-syed');
      should.not.exist(cache);
    });
  });
});
