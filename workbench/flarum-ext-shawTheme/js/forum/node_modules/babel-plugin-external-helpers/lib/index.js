"use strict";

exports.__esModule = true;

exports.default = function (_ref) {
  var t = _ref.types;

  return {
    pre: function pre(file) {
      file.set("helpersNamespace", t.identifier("babelHelpers"));
    }
  };
};

module.exports = exports["default"];