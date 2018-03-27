/**
 * The `anchorScroll` utility saves the scroll position relative to an element,
 * and then restores it after a callback has been run.
 *
 * This is useful if a redraw will change the page's content above the viewport.
 * Normally doing this will result in the content in the viewport being pushed
 * down or pulled up. By wrapping the redraw with this utility, the scroll
 * position can be anchor to an element that is in or below the viewport, so
 * the content in the viewport will stay the same.
 *
 * @param {DOMElement} element The element to anchor the scroll position to.
 * @param {Function} callback The callback to run that will change page content.
 */
export default function anchorScroll(element, callback) {
  const $window = $(window);
  const relativeScroll = $(element).offset().top - $window.scrollTop();

  callback();

  $window.scrollTop($(element).offset().top - relativeScroll);
}
