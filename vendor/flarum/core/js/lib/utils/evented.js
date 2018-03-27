/**
 * The `evented` mixin provides methods allowing an object to trigger events,
 * running externally registered event handlers.
 */
export default {
  /**
   * Arrays of registered event handlers, grouped by the event name.
   *
   * @type {Object}
   * @protected
   */
  handlers: null,

  /**
   * Get all of the registered handlers for an event.
   *
   * @param {String} event The name of the event.
   * @return {Array}
   * @protected
   */
  getHandlers(event) {
    this.handlers = this.handlers || {};

    this.handlers[event] = this.handlers[event] || [];

    return this.handlers[event];
  },

  /**
   * Trigger an event.
   *
   * @param {String} event The name of the event.
   * @param {...*} args Arguments to pass to event handlers.
   * @public
   */
  trigger(event, ...args) {
    this.getHandlers(event).forEach(handler => handler.apply(this, args));
  },

  /**
   * Register an event handler.
   *
   * @param {String} event The name of the event.
   * @param {function} handler The function to handle the event.
   */
  on(event, handler) {
    this.getHandlers(event).push(handler);
  },

  /**
   * Register an event handler so that it will run only once, and then
   * unregister itself.
   *
   * @param {String} event The name of the event.
   * @param {function} handler The function to handle the event.
   */
  one(event, handler) {
    const wrapper = function() {
      handler.apply(this, arguments);

      this.off(event, wrapper);
    };

    this.getHandlers(event).push(wrapper);
  },

  /**
   * Unregister an event handler.
   *
   * @param {String} event The name of the event.
   * @param {function} handler The function that handles the event.
   */
  off(event, handler) {
    const handlers = this.getHandlers(event);
    const index = handlers.indexOf(handler);

    if (index !== -1) {
      handlers.splice(index, 1);
    }
  }
}
