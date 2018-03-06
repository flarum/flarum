/**
 * The `slidable` utility adds touch gestures to an element so that it can be
 * slid away to reveal controls underneath, and then released to activate those
 * controls.
 *
 * It relies on the element having children with particular CSS classes.
 * TODO: document
 *
 * @param {DOMElement} element
 * @return {Object}
 * @property {function} reset Revert the slider to its original position. This
 *     should be called, for example, when a controls dropdown is closed.
 */
export default function slidable(element) {
  const $element = $(element);
  const threshold = 50;

  let $underneathLeft;
  let $underneathRight;

  let startX;
  let startY;
  let couldBeSliding = false;
  let isSliding = false;
  let pos = 0;

  /**
   * Animate the slider to a new position.
   *
   * @param {Integer} newPos
   * @param {Object} [options]
   */
  const animatePos = (newPos, options = {}) => {
    // Since we can't animate the transform property with jQuery, we'll use a
    // bit of a workaround. We set up the animation with a step function that
    // will set the transform property, but then we animate an unused property
    // (background-position-x) with jQuery.
    options.duration = options.duration || 'fast';
    options.step = function(x) {
      $(this).css('transform', 'translate(' + x + 'px, 0)');
    };

    $element.find('.Slidable-content').animate({'background-position-x': newPos}, options);
  };

  /**
   * Revert the slider to its original position.
   */
  const reset = () => {
    animatePos(0, {
      complete: function() {
        $element.removeClass('sliding');
        $underneathLeft.hide();
        $underneathRight.hide();
        isSliding = false;
      }
    });
  };

  $element.find('.Slidable-content')
    .on('touchstart', function(e) {
      // Update the references to the elements underneath the slider, provided
      // they're not disabled.
      $underneathLeft = $element.find('.Slidable-underneath--left:not(.disabled)');
      $underneathRight = $element.find('.Slidable-underneath--right:not(.disabled)');

      startX = e.originalEvent.targetTouches[0].clientX;
      startY = e.originalEvent.targetTouches[0].clientY;

      couldBeSliding = true;
      pos = 0;
    })

    .on('touchmove', function(e) {
      const newX = e.originalEvent.targetTouches[0].clientX;
      const newY = e.originalEvent.targetTouches[0].clientY;

      // Once the user moves their touch in a direction that's more up/down than
      // left/right, we'll assume they're scrolling the page. But if they do
      // move in a horizontal direction at first, then we'll lock their touch
      // into the slider.
      if (couldBeSliding && Math.abs(newX - startX) > Math.abs(newY - startY)) {
        isSliding = true;
      }
      couldBeSliding = false;

      if (isSliding) {
        pos = newX - startX;

        // If there are controls underneath the either side, then we'll show/hide
        // them depending on the slider's position. We also make the controls
        // icon get a bit bigger the further they slide.
        const toggle = ($underneath, side) => {
          if ($underneath.length) {
            const active = side === 'left' ? pos > 0 : pos < 0;

            if (active && $underneath.hasClass('Slidable-underneath--elastic')) {
              pos -= pos * 0.5;
            }
            $underneath.toggle(active);

            const scale = Math.max(0, Math.min(1, (Math.abs(pos) - 25) / threshold));
            $underneath.find('.icon').css('transform', 'scale(' + scale + ')');
          } else {
            pos = Math[side === 'left' ? 'min' : 'max'](0, pos);
          }
        };

        toggle($underneathLeft, 'left');
        toggle($underneathRight, 'right');

        $(this).css('transform', 'translate(' + pos + 'px, 0)');
        $(this).css('background-position-x', pos + 'px');

        $element.toggleClass('sliding', !!pos);

        e.preventDefault();
      }
    })

    .on('touchend', function() {
      // If the user releases the touch and the slider is past the threshold
      // position on either side, then we will activate the control for that
      // side. We will also animate the slider's position all the way to the
      // other side, or back to its original position, depending on whether or
      // not the side is 'elastic'.
      const activate = $underneath => {
        $underneath.click();

        if ($underneath.hasClass('Slidable-underneath--elastic')) {
          reset();
        } else {
          animatePos((pos > 0 ? 1 : -1) * $element.width());
        }
      };

      if ($underneathRight.length && pos < -threshold) {
        activate($underneathRight);
      } else if ($underneathLeft.length && pos > threshold) {
        activate($underneathLeft);
      } else {
        reset();
      }

      couldBeSliding = false;
      isSliding = false;
    });

  return {reset};
};
