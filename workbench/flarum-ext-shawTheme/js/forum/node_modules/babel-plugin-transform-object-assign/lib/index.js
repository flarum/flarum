"use strict";

exports.__esModule = true;

exports.default = function () {
  return {
    visitor: {
      CallExpression: function CallExpression(path, file) {
        if (path.get("callee").matchesPattern("Object.assign")) {
          path.node.callee = file.addHelper("extends");
        }
      }
    }
  };
};

module.exports = exports["default"];