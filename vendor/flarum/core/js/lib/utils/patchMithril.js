import Component from '../Component';

export default function patchMithril(global) {
  const mo = global.m;

  const m = function(comp, ...args) {
    if (comp.prototype && comp.prototype instanceof Component) {
      return comp.component(...args);
    }

    const node = mo.apply(this, arguments);

    if (node.attrs.bidi) {
      m.bidi(node, node.attrs.bidi);
    }

    if (node.attrs.route) {
      node.attrs.href = node.attrs.route;
      node.attrs.config = m.route;

      delete node.attrs.route;
    }

    return node;
  };

  Object.keys(mo).forEach(key => m[key] = mo[key]);

  /**
   * Redraw only if not in the middle of a computation (e.g. a route change).
   *
   * @return {void}
   */
  m.lazyRedraw = function() {
    m.startComputation();
    m.endComputation();
  };

  global.m = m;
}
