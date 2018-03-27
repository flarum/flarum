import { extend } from 'flarum/extend';
import Discussion from 'flarum/models/Discussion';
import Badge from 'flarum/components/Badge';

export default function addStickyBadge() {
  extend(Discussion.prototype, 'badges', function(badges) {
    if (this.isSticky()) {
      badges.add('sticky', Badge.component({
        type: 'sticky',
        label: app.translator.trans('flarum-sticky.forum.badge.sticky_tooltip'),
        icon: 'thumb-tack'
      }), 10);
    }
  });
}
