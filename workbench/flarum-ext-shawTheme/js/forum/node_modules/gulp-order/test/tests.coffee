order = require "../src"
path = require "path"
{ File } = require "gulp-util"
{ expect } = require "chai"
require "mocha"

cwd = "/home/johndoe/"

newFile = (filepath, base) ->
  base ?= cwd
  
  new File
    path: path.join(base, filepath)
    base: base
    cwd: cwd
    contents: new Buffer("")

describe "gulp-order", ->
  describe "order()", ->
    it "orders files", (done) ->
      stream = order(["foo.js", "bar.js"])
      
      files = []
      stream.on "data", files.push.bind(files)
      stream.on "end", ->
        expect(files.length).to.equal 4
        expect(files[0].relative).to.equal "foo.js"
        expect(files[1].relative).to.equal "bar.js"
        expect(files[2].relative).to.equal "baz-a.js"
        expect(files[3].relative).to.equal "baz-b.js"
        done()

      stream.write newFile("baz-b.js")      
      stream.write newFile("bar.js")  
      stream.write newFile("baz-a.js")
      stream.write newFile("foo.js")
      stream.end()
    
    it "supports globs", (done) ->
      stream = order(["vendor/**/*", "app/**/*"])
      
      files = []
      stream.on "data", files.push.bind(files)
      stream.on "end", ->
        expect(files.length).to.equal 5
        expect(files[0].relative).to.equal "vendor/f/b.js"
        expect(files[1].relative).to.equal "vendor/z/a.js"
        expect(files[2].relative).to.equal "app/a.js"
        expect(files[3].relative).to.equal "other/a.js"
        expect(files[4].relative).to.equal "other/b/a.js"
        done()
        
      stream.write newFile("vendor/f/b.js")
      stream.write newFile("app/a.js")
      stream.write newFile("vendor/z/a.js")
      stream.write newFile("other/a.js")
      stream.write newFile("other/b/a.js")
      stream.end()
    
    it "supports a custom base", (done) ->
      stream = order(['scripts/b.css'], base: cwd)

      files = []
      stream.on "data", files.push.bind(files)
      stream.on "end", ->
        expect(files.length).to.equal 2
        expect(files[0].relative).to.equal "b.css"
        expect(files[1].relative).to.equal "a.css"
        done()

      stream.write newFile("a.css", path.join(cwd, "scripts/"))
      stream.write newFile("b.css", path.join(cwd, "scripts/"))
      stream.end()
      
    it "warns on relative paths in order list", ->
      expect ->
        order(['./user.js'])
      .to.throw "Don't start patterns with `./` - they will never match. Just leave out `./`"