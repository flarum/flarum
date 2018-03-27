/**
 * The `Pane` class manages the page's discussion list sidepane. The pane is a
 * part of the content view (DiscussionPage component), but its visibility is
 * determined by CSS classes applied to the outer page element. This class
 * manages the application of those CSS classes.
 */
export default class Pane {
  constructor(element) {
    /**
     * The localStorage key to store the pane's pinned state with.
     *
     * @type {String}
     * @protected
     */
    this.pinnedKey = 'panePinned';

    /**
     * The page element.
     *
     * @type {jQuery}
     * @protected
     */
    this.$element = $(element);

    /**
     * Whether or not the pane is currently pinned.
     *
     * @type {Boolean}
     * @protected
     */
    this.pinned = localStorage.getItem(this.pinnedKey) === 'true';

    /**
     * Whether or not the pane is currently exists.
     *
     * @type {Boolean}
     * @protected
     */
    this.active = false;

    /**
     * Whether or not the pane is currently showing, or is hidden off the edge
     * of the screen.
     *
     * @type {Boolean}
     * @protected
     */
    this.showing = false;

    this.render();
  }

  /**
   * Enable the pane.
   *
   * @public
   */
  enable() {
    this.active = true;
    this.render();
  }

  /**
   * Disable the pane.
   *
   * @public
   */
  disable() {
    this.active = false;
    this.showing = false;
    this.render();
  }

  /**
   * Show the pane.
   *
   * @public
   */
  show() {
    clearTimeout(this.hideTimeout);
    this.showing = true;
    this.render();
  }

  /**
   * Hide the pane.
   *
   * @public
   */
  hide() {
    this.showing = false;
    this.render();
  }

  /**
   * Begin a timeout to hide the pane, which can be cancelled by showing the
   * pane.
   *
   * @public
   */
  onmouseleave() {
    this.hideTimeout = setTimeout(this.hide.bind(this), 250);
  }

  /**
   * Toggle whether or not the pane is pinned.
   *
   * @public
   */
  togglePinned() {
    this.pinned = !this.pinned;

    localStorage.setItem(this.pinnedKey, this.pinned ? 'true' : 'false');

    this.render();
  }

  /**
   * Apply the appropriate CSS classes to the page element.
   *
   * @protected
   */
  render() {
    this.$element
      .toggleClass('panePinned', this.pinned)
      .toggleClass('hasPane', this.active)
      .toggleClass('paneShowing', this.showing);
  }
}
