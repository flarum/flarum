"use strict";

exports.__esModule = true;
exports.definitions = undefined;

exports.default = function (_ref) {
  var t = _ref.types;

  function getRuntimeModuleName(opts) {
    return opts.moduleName || "babel-runtime";
  }

  function has(obj, key) {
    return Object.prototype.hasOwnProperty.call(obj, key);
  }

  var HELPER_BLACKLIST = ["interopRequireWildcard", "interopRequireDefault"];

  return {
    pre: function pre(file) {
      var moduleName = getRuntimeModuleName(this.opts);

      if (this.opts.helpers !== false) {
        file.set("helperGenerator", function (name) {
          if (HELPER_BLACKLIST.indexOf(name) < 0) {
            return file.addImport(moduleName + "/helpers/" + name, "default", name);
          }
        });
      }

      this.setDynamic("regeneratorIdentifier", function () {
        return file.addImport(moduleName + "/regenerator", "default", "regeneratorRuntime");
      });
    },


    visitor: {
      ReferencedIdentifier: function ReferencedIdentifier(path, state) {
        var node = path.node,
            parent = path.parent,
            scope = path.scope;


        if (node.name === "regeneratorRuntime" && state.opts.regenerator !== false) {
          path.replaceWith(state.get("regeneratorIdentifier"));
          return;
        }

        if (state.opts.polyfill === false) return;

        if (t.isMemberExpression(parent)) return;
        if (!has(_definitions2.default.builtins, node.name)) return;
        if (scope.getBindingIdentifier(node.name)) return;

        var moduleName = getRuntimeModuleName(state.opts);
        path.replaceWith(state.addImport(moduleName + "/core-js/" + _definitions2.default.builtins[node.name], "default", node.name));
      },
      CallExpression: function CallExpression(path, state) {
        if (state.opts.polyfill === false) return;

        if (path.node.arguments.length) return;

        var callee = path.node.callee;
        if (!t.isMemberExpression(callee)) return;
        if (!callee.computed) return;
        if (!path.get("callee.property").matchesPattern("Symbol.iterator")) return;

        var moduleName = getRuntimeModuleName(state.opts);
        path.replaceWith(t.callExpression(state.addImport(moduleName + "/core-js/get-iterator", "default", "getIterator"), [callee.object]));
      },
      BinaryExpression: function BinaryExpression(path, state) {
        if (state.opts.polyfill === false) return;

        if (path.node.operator !== "in") return;
        if (!path.get("left").matchesPattern("Symbol.iterator")) return;

        var moduleName = getRuntimeModuleName(state.opts);
        path.replaceWith(t.callExpression(state.addImport(moduleName + "/core-js/is-iterable", "default", "isIterable"), [path.node.right]));
      },

      MemberExpression: {
        enter: function enter(path, state) {
          if (state.opts.polyfill === false) return;
          if (!path.isReferenced()) return;

          var node = path.node;

          var obj = node.object;
          var prop = node.property;

          if (!t.isReferenced(obj, node)) return;
          if (node.computed) return;
          if (!has(_definitions2.default.methods, obj.name)) return;

          var methods = _definitions2.default.methods[obj.name];
          if (!has(methods, prop.name)) return;

          if (path.scope.getBindingIdentifier(obj.name)) return;

          if (obj.name === "Object" && prop.name === "defineProperty" && path.parentPath.isCallExpression()) {
            var call = path.parentPath.node;
            if (call.arguments.length === 3 && t.isLiteral(call.arguments[1])) return;
          }

          var moduleName = getRuntimeModuleName(state.opts);
          path.replaceWith(state.addImport(moduleName + "/core-js/" + methods[prop.name], "default", obj.name + "$" + prop.name));
        },
        exit: function exit(path, state) {
          if (state.opts.polyfill === false) return;
          if (!path.isReferenced()) return;

          var node = path.node;

          var obj = node.object;

          if (!has(_definitions2.default.builtins, obj.name)) return;
          if (path.scope.getBindingIdentifier(obj.name)) return;

          var moduleName = getRuntimeModuleName(state.opts);
          path.replaceWith(t.memberExpression(state.addImport(moduleName + "/core-js/" + _definitions2.default.builtins[obj.name], "default", obj.name), node.property, node.computed));
        }
      }
    }
  };
};

var _definitions = require("./definitions");

var _definitions2 = _interopRequireDefault(_definitions);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

exports.definitions = _definitions2.default;