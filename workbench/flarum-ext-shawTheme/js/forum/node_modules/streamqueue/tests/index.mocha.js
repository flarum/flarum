var assert = require('assert');
var Stream = require('readable-stream');
var StreamTest = require('streamtest');
var StreamQueue = require('../src');



// Tests
describe('StreamQueue', function() {
  
  // Iterating through versions
  StreamTest.versions.forEach(function(version) {

    describe('for ' + version + ' streams', function() {

      describe('in binary mode', function() {

        describe('and with async streams', function() {

          it('should work with functionnal API', function(done) {
            StreamQueue(
              StreamTest[version].fromChunks(['wa','dup']),
              StreamTest[version].fromChunks(['pl','op']),
              StreamTest[version].fromChunks(['ki','koo','lol'])
            ).pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, 'wadupplopkikoolol');
              done();
            }));
          });

          it('should work with functionnal API and options', function(done) {
            StreamQueue({},
              StreamTest[version].fromChunks(['wa','dup']),
              StreamTest[version].fromChunks(['pl','op']),
              StreamTest[version].fromChunks(['ki','koo','lol'])
            ).pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, 'wadupplopkikoolol');
              done();
            }));
          });

          it('should work with POO API', function(done) {
            var queue = new StreamQueue();
            queue.queue(StreamTest[version].fromChunks(['wa','dup']));
            queue.queue(StreamTest[version].fromChunks(['pl','op']));
            queue.queue(StreamTest[version].fromChunks(['ki','koo','lol']));
            assert.equal(queue.length, 3);
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, 'wadupplopkikoolol');
              done();
            }));
            queue.done();
          });

          it('should pause streams in flowing mode', function(done) {
            var queue = new StreamQueue({
              pauseFlowingStream: true,
              resumeFlowingStream: true
            });
            var flowingStream = StreamTest[version].fromChunks(['pl','op']);
            flowingStream.on('data', function() {});
            queue.queue(StreamTest[version].fromChunks(['wa','dup']));
            queue.queue(flowingStream);
            queue.queue(StreamTest[version].fromChunks(['ki','koo','lol']));
            assert.equal(queue.length, 3);
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, 'wadupplopkikoolol');
              done();
            }));
            queue.done();
          });

          it('should work with POO API and options', function(done) {
            var queue = new StreamQueue({
              pauseFlowingStream: true,
              resumeFlowingStream: true
            });
            queue.queue(StreamTest[version].fromChunks(['wa','dup']));
            queue.queue(StreamTest[version].fromChunks(['pl','op']));
            queue.queue(StreamTest[version].fromChunks(['ki','koo','lol']));
            assert.equal(queue.length, 3);
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, 'wadupplopkikoolol');
              done();
            }));
            queue.done();
          });

          it('should work with POO API and a late done call', function(done) {
            var queue = new StreamQueue();
            queue.queue(StreamTest[version].fromChunks(['wa','dup']));
            queue.queue(StreamTest[version].fromChunks(['pl','op']));
            queue.queue(StreamTest[version].fromChunks(['ki','koo','lol']));
            assert.equal(queue.length, 3);
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, 'wadupplopkikoolol');
              done();
            }));
            setTimeout(function() {
              queue.done();
            }, 100);
          });

          it('should work with POO API and no stream plus sync done', function(done) {
            var queue = new StreamQueue();
            assert.equal(queue.length, 0);
            queue.queue();
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, '');
              done();
            }));
            queue.done();
          });

          it('should work with POO API and no stream plus async done', function(done) {
            var queue = new StreamQueue();
            assert.equal(queue.length, 0);
            queue.queue();
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, '');
              done();
            }));
            setTimeout(function() {
              queue.done();
            }, 100);
          });

          it('should work with POO API and a streamqueue stream plus async done', function(done) {
            var queue = new StreamQueue();
            var child = new StreamQueue();
            queue.queue(child);
            assert.equal(queue.length, 1);
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, '');
              done();
            }));
            child.done();
            setTimeout(function() {
              queue.done();
            }, 100);
          });

          it('should work with POO API and a streamqueue stream plus async done', function(done) {
            var queue = new StreamQueue();
            var child = new StreamQueue();
            queue.queue(child);
            assert.equal(queue.length, 1);
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, '');
              done();
            }));
            child.done();
            queue.done();
          });

          it('should work with POO API and a streamqueue ended stream plus async done', function(done) {
            var queue = new StreamQueue();
            var child = new StreamQueue();
            queue.queue(child);
            child.done();
            assert.equal(queue.length, 1);
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, '');
              done();
            }));
            setTimeout(function() {
              queue.done();
            }, 100);
          });

          it('should fire end asynchronously with streams', function(done) {
            var queue = new StreamQueue();
            var ended = false;
            queue.queue(StreamTest[version].fromChunks(['wa','dup'])
              .on('end', function() {
                assert.equal(ended, false);
              }));
            queue.queue(StreamTest[version].fromChunks(['pl','op'])
              .on('end', function() {
                assert.equal(ended, false);
              }));
            queue.queue(StreamTest[version].fromChunks(['ki','koo','lol'])
              .on('end', function() {
                assert.equal(ended, false);
              }));
            assert.equal(queue.length, 3);
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, 'wadupplopkikoolol');
              done();
            }));
            queue.on('end', function() {
              ended = true;
            });
            queue.done();
            assert.equal(ended, false);
          });

          it('should fire end asynchronously when empty', function(done) {
            var queue = new StreamQueue();
            var ended = false;
            assert.equal(queue.length, 0);
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, '');
              done();
            }));
            queue.on('end', function() {
              ended = true;
            });
            queue.done();
            assert.equal(ended, false);
          });

          it('should work with POO API and a streamqueue ended stream plus sync done', function(done) {
            var queue = new StreamQueue();
            var child = new StreamQueue();
            queue.queue(child);
            child.done();
            assert.equal(queue.length, 1);
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, '');
              done();
            }));
            queue.done();
          });

          it('should work with POO API and a streamqueue ended stream plus async done', function(done) {
            var queue = new StreamQueue();
            var child = new StreamQueue();
            child.done();
            queue.queue(child);
            assert.equal(queue.length, 1);
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, '');
              done();
            }));
            setTimeout(function() {
              queue.done();
            }, 100);
          });

          it('should work with POO API and a streamqueue ended stream plus sync done', function(done) {
            var queue = new StreamQueue();
            var child = new StreamQueue();
            child.done();
            queue.queue(child);
            assert.equal(queue.length, 1);
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, '');
              done();
            }));
            queue.done();
          });

          if('v2' === version)
          it('should reemit errors', function(done) {
            var _err;
            var queue = new StreamQueue();
            queue.queue(StreamTest[version].fromErroredChunks(new Error('Aouch!'), []));
            queue.queue(StreamTest[version].fromChunks(['wa','dup']));
            queue.queue(StreamTest[version].fromChunks(['pl','op']));
            queue.queue(StreamTest[version].fromChunks(['ki','koo','lol']));
            assert.equal(queue.length, 4);
            queue.on('error', function(err) {
              _err = err;
            });
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert(_err);
              assert.equal(_err.message, 'Aouch!');
              assert.equal(text, 'wadupplopkikoolol');
              done();
            }));
            queue.done();
          });

          if('v2' === version)
          it('should reemit errors elsewhere', function(done) {
            var _err;
            var queue = new StreamQueue();
            queue.queue(StreamTest[version].fromChunks(['wa','dup']));
            queue.queue(StreamTest[version].fromChunks(['pl','op']));
            queue.queue(StreamTest[version].fromErroredChunks(new Error('Aouch!'), []));
            queue.queue(StreamTest[version].fromChunks(['ki','koo','lol']));
            assert.equal(queue.length, 4);
            queue.on('error', function(err) {
              _err = err;
            });
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert(_err);
              assert.equal(_err.message, 'Aouch!');
              assert.equal(text, 'wadupplopkikoolol');
              done();
            }));
            queue.done();
          });

        });

        describe('and with sync streams', function() {

          it('should work with functionnal API', function(done) {
            var stream1 = StreamTest[version].syncReadableChunks();
            var stream2 = StreamTest[version].syncReadableChunks();
            var stream3 = StreamTest[version].syncReadableChunks();
            StreamQueue(
              stream1, stream2, stream3
            ).pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, 'wadupplopkikoolol');
              done();
            }));
            StreamTest[version].syncWrite(stream1, ['wa','dup']);
            StreamTest[version].syncWrite(stream2, ['pl','op']);
            StreamTest[version].syncWrite(stream3, ['ki','koo', 'lol']);
          });

          it('should work with POO API', function(done) {
            var queue = new StreamQueue();
            var stream1 = StreamTest[version].syncReadableChunks();
            var stream2 = StreamTest[version].syncReadableChunks();
            var stream3 = StreamTest[version].syncReadableChunks();
            queue.queue(stream1);
            queue.queue(stream2);
            queue.queue(stream3);
            StreamTest[version].syncWrite(stream1, ['wa','dup']);
            StreamTest[version].syncWrite(stream2, ['pl','op']);
            StreamTest[version].syncWrite(stream3, ['ki','koo', 'lol']);
            assert.equal(queue.length, 3);
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, 'wadupplopkikoolol');
              done();
            }));
            queue.done();
          });

          it('should emit an error when calling done twice', function(done) {
            var queue = new StreamQueue();
            var stream1 = StreamTest[version].syncReadableChunks();
            var stream2 = StreamTest[version].syncReadableChunks();
            var stream3 = StreamTest[version].syncReadableChunks();
            queue.queue(stream1);
            queue.queue(stream2);
            queue.queue(stream3);
            StreamTest[version].syncWrite(stream1, ['wa','dup']);
            StreamTest[version].syncWrite(stream2, ['pl','op']);
            StreamTest[version].syncWrite(stream3, ['ki','koo', 'lol']);
            assert.equal(queue.length, 3);
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, 'wadupplopkikoolol');
              done();
            }));
            queue.done();
            assert.throws(function() {
              queue.done();
            });
          });

          it('should emit an error when queueing after done was called', function(done) {
            var queue = new StreamQueue();
            var stream1 = StreamTest[version].syncReadableChunks();
            var stream2 = StreamTest[version].syncReadableChunks();
            var stream3 = StreamTest[version].syncReadableChunks();
            queue.queue(stream1);
            queue.queue(stream2);
            queue.queue(stream3);
            StreamTest[version].syncWrite(stream1, ['wa','dup']);
            StreamTest[version].syncWrite(stream2, ['pl','op']);
            StreamTest[version].syncWrite(stream3, ['ki','koo', 'lol']);
            assert.equal(queue.length, 3);
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, 'wadupplopkikoolol');
              done();
            }));
            queue.done();
            assert.throws(function() {
              queue.queue(StreamTest[version].syncReadableChunks());
            });
          });

          if('v2' === version)
          it('should reemit errors', function(done) {
            var _err;
            var queue = new StreamQueue();
            var stream1 = StreamTest[version].syncReadableChunks();
            var stream2 = StreamTest[version].syncReadableChunks();
            var stream3 = StreamTest[version].syncReadableChunks();
            var stream4 = StreamTest[version].syncReadableChunks();
            queue.queue(stream1);
            queue.queue(stream2);
            queue.queue(stream3);
            queue.queue(stream4);
            queue.on('error', function(err) {
              _err = err;
            });
            StreamTest[version].syncError(stream1, new Error('Aouch!'));
            StreamTest[version].syncWrite(stream2, ['wa','dup']);
            StreamTest[version].syncWrite(stream3, ['pl','op']);
            StreamTest[version].syncWrite(stream4, ['ki','koo', 'lol']);
            assert.equal(queue.length, 4);
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert(_err);
              assert.equal(_err.message, 'Aouch!');
              assert.equal(text, 'wadupplopkikoolol');
              done();
            }));
            queue.done();
          });

        });

        describe('and with functions returning streams', function() {

          it('should work with functionnal API', function(done) {
            StreamQueue(
              StreamTest[version].fromChunks.bind(null, ['wa','dup']),
              StreamTest[version].fromChunks.bind(null, ['pl','op']),
              StreamTest[version].fromChunks.bind(null, ['ki','koo','lol'])
            ).pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, 'wadupplopkikoolol');
              done();
            }));
          });

          it('should work with functionnal API and options', function(done) {
            StreamQueue(
              StreamTest[version].fromChunks.bind(null, ['wa','dup']),
              StreamTest[version].fromChunks.bind(null, ['pl','op']),
              StreamTest[version].fromChunks.bind(null, ['ki','koo','lol'])
            ).pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, 'wadupplopkikoolol');
              done();
            }));
          });

          it('should work with POO API', function(done) {
            var queue = new StreamQueue();
            queue.queue(StreamTest[version].fromChunks.bind(null, ['wa','dup']));
            queue.queue(StreamTest[version].fromChunks.bind(null, ['pl','op']));
            queue.queue(StreamTest[version].fromChunks.bind(null, ['ki','koo','lol']));
            assert.equal(queue.length, 3);
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, 'wadupplopkikoolol');
              done();
            }));
            queue.done();
          });

          it('should pause streams in flowing mode', function(done) {
            var queue = new StreamQueue({
              pauseFlowingStream: true,
              resumeFlowingStream: true
            });
            queue.queue(StreamTest[version].fromChunks.bind(null, ['wa','dup']));
            queue.queue(function() {
              var stream = StreamTest[version].fromChunks(['pl','op']);
              stream.on('data', function() {});
              return stream;
            });
            queue.queue(StreamTest[version].fromChunks.bind(null, ['ki','koo','lol']));
            assert.equal(queue.length, 3);
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, 'wadupplopkikoolol');
              done();
            }));
            queue.done();
          });

          it('should work with POO API and options', function(done) {
            var queue = new StreamQueue({
              pauseFlowingStream: true,
              resumeFlowingStream: true
            });
            queue.queue(StreamTest[version].fromChunks.bind(null, ['wa','dup']));
            queue.queue(StreamTest[version].fromChunks.bind(null, ['pl','op']));
            queue.queue(StreamTest[version].fromChunks.bind(null, ['ki','koo','lol']));
            assert.equal(queue.length, 3);
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, 'wadupplopkikoolol');
              done();
            }));
            queue.done();
          });

          it('should work with POO API and a late done call', function(done) {
            var queue = new StreamQueue();
            queue.queue(StreamTest[version].fromChunks.bind(null, ['wa','dup']));
            queue.queue(StreamTest[version].fromChunks.bind(null, ['pl','op']));
            queue.queue(StreamTest[version].fromChunks.bind(null, ['ki','koo','lol']));
            assert.equal(queue.length, 3);
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert.equal(text, 'wadupplopkikoolol');
              done();
            }));
            setTimeout(function() {
              queue.done();
            }, 100);
          });

          if('v2' === version)
          it('should reemit errors', function(done) {
            var _err;
            var queue = new StreamQueue();
            queue.queue(StreamTest[version].fromErroredChunks.bind(null, new Error('Aouch!'), []));
            queue.queue(StreamTest[version].fromChunks.bind(null, ['wa','dup']));
            queue.queue(StreamTest[version].fromChunks.bind(null, ['pl','op']));
            queue.queue(StreamTest[version].fromChunks.bind(null, ['ki','koo','lol']));
            assert.equal(queue.length, 4);
            queue.on('error', function(err) {
              _err = err;
            });
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert(_err);
              assert.equal(_err.message, 'Aouch!');
              assert.equal(text, 'wadupplopkikoolol');
              done();
            }));
            queue.done();
          });

          if('v2' === version)
          it('should reemit errors elsewhere', function(done) {
            var _err;
            var queue = new StreamQueue();
            queue.queue(StreamTest[version].fromChunks.bind(null, ['wa','dup']));
            queue.queue(StreamTest[version].fromChunks.bind(null, ['pl','op']));
            queue.queue(StreamTest[version].fromErroredChunks.bind(null, new Error('Aouch!'), []));
            queue.queue(StreamTest[version].fromChunks.bind(null, ['ki','koo','lol']));
            assert.equal(queue.length, 4);
            queue.on('error', function(err) {
              _err = err;
            });
            queue.pipe(StreamTest[version].toText(function(err, text) {
              if(err) {
                done(err);
              }
              assert(_err);
              assert.equal(_err.message, 'Aouch!');
              assert.equal(text, 'wadupplopkikoolol');
              done();
            }));
            queue.done();
          });

        });

      });

      describe('in object mode', function() {

        it('should work', function(done) {
          var queue = new StreamQueue({objectMode: true});
          queue.queue(StreamTest[version].fromObjects([{s:'wa'},{s:'dup'}]));
          queue.queue(StreamTest[version].fromObjects([{s:'pl'},{s:'op'}]));
          queue.queue(StreamTest[version].fromObjects([{s:'ki'},{s:'koo'},{s:'lol'}]));
          queue.pipe(StreamTest[version].toObjects(function(err, objs) {
            if(err) {
              done(err);
            }
            assert.deepEqual(objs, [{s:'wa'},{s:'dup'},{s:'pl'},{s:'op'},{s:'ki'},{s:'koo'},{s:'lol'}]);
            done();
          }));
          queue.done();
        });

      });

    });

  });

});

