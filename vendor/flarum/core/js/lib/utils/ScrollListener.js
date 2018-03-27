const scroll = window.requestAnimationFrame ||
  window.webkitRequestAnimationFrame ||
  window.mozRequestAnimationFrame ||
  window.msRequestAnimationFrame ||
  window.oRequestAnimationFrame ||
  (callback => window.setTimeout(callback, 1000 / 60));

/**
 * The `ScrollListener` class sets up a listener that handles window scroll
 * events.
 */
export default class ScrollListener {
  /**
   * @param {Function} callback The callback to run when the scroll position
   *     changes.
   * @public
   */
  constructor(callback) {
    this.callback = callback;
    this.lastTop = -1;
  }

  /**
   * On each animation frame, as long as the listener is active, run the
   * `update` method.
   *
   * @protected
   */
  loop() {
    if (!this.active) return;

    this.update();

    scroll(this.loop.bind(this));
  }

  /**
   * Check if the scroll position has changed; if it has, run the handler.
   *
   * @param {Boolean} [force=false] Whether or not to force the handler to be
   *     run, even if the scroll position hasn't changed.
   * @public
   */
  update(force) {
    const top = window.pageYOffset;

    if (this.lastTop !== top || force) {
      this.callback(top);
      this.lastTop = top;
    }
  }

  /**
   * Start listening to and handling the window's scroll position.
   *
   * @public
   */
  start() {
    if (!this.active) {
      this.active = true;
      this.loop();
    }
  }

  /**
   * Stop listening to and handling the window's scroll position.
   *
   * @public
   */
  stop() {
    this.active = false;
  }
}
