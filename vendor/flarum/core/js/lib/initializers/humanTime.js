import humanTimeUtil from 'flarum/utils/humanTime';

function updateHumanTimes() {
  $('[data-humantime]').each(function() {
    const $this = $(this);
    const ago = humanTimeUtil($this.attr('datetime'));

    $this.html(ago);
  });
}

/**
 * The `humanTime` initializer sets up a loop every 1 second to update
 * timestamps rendered with the `humanTime` helper.
 */
export default function humanTime() {
  setInterval(updateHumanTimes, 10000);
}
