/**
 * Setup the sidebar DOM element to be affixed to the top of the viewport
 * using Bootstrap's affix plugin.
 *
 * @param {DOMElement} element
 * @param {Boolean} isInitialized
 * @param {Object} context
 */
export default function affixSidebar(element, isInitialized, context) {
  if (isInitialized) return;

  const onresize = () => {
    const $sidebar = $(element);
    const $header = $('#header');
    const $footer = $('#footer');
    const $affixElement = $sidebar.find('> ul');

    $(window).off('.affix');
    $affixElement
      .removeClass('affix affix-top affix-bottom')
      .removeData('bs.affix');

    // Don't affix the sidebar if it is taller than the viewport (otherwise
    // there would be no way to scroll through its content).
    if ($sidebar.outerHeight(true) > $(window).height() - $header.outerHeight(true)) return;

    $affixElement.affix({
      offset: {
        top: () => $sidebar.offset().top - $header.outerHeight(true) - parseInt($sidebar.css('margin-top'), 10),
        bottom: () => this.bottom = $footer.outerHeight(true)
      }
    });
  };

  // Register the affix plugin to execute on every window resize (and trigger)
  $(window).on('resize', onresize).resize();

  context.onunload = () => {
    $(window).off('resize', onresize);
  }
}
