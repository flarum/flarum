/**
 * The `SubtreeRetainer` class represents a Mithril virtual DOM subtree. It
 * keeps track of a number of pieces of data, allowing the subtree to be
 * retained if none of them have changed.
 *
 * @example
 * // constructor
 * this.subtree = new SubtreeRetainer(
 *   () => this.props.post.freshness,
 *   () => this.showing
 * );
 * this.subtree.check(() => this.props.user.freshness);
 *
 * // view
 * this.subtree.retain() || 'expensive expression'
 *
 * @see https://lhorie.github.io/mithril/mithril.html#persisting-dom-elements-across-route-changes
 */
export default class SubtreeRetainer {
  /**
   * @param {...callbacks} callbacks Functions returning data to keep track of.
   */
  constructor(...callbacks) {
    this.callbacks = callbacks;
    this.data = {};
  }

  /**
   * Return a virtual DOM directive that will retain a subtree if no data has
   * changed since the last check.
   *
   * @return {Object|false}
   * @public
   */
  retain() {
    let needsRebuild = false;

    this.callbacks.forEach((callback, i) => {
      const result = callback();

      if (result !== this.data[i]) {
        this.data[i] = result;
        needsRebuild = true;
      }
    });

    return needsRebuild ? false : {subtree: 'retain'};
  }

  /**
   * Add another callback to be checked.
   *
   * @param {...Function} callbacks
   * @public
   */
  check(...callbacks) {
    this.callbacks = this.callbacks.concat(callbacks);
  }

  /**
   * Invalidate the subtree, forcing it to be rerendered.
   *
   * @public
   */
  invalidate() {
    this.data = {};
  }
}
