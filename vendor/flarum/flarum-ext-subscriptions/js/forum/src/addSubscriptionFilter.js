import { extend } from 'flarum/extend';
import LinkButton from 'flarum/components/LinkButton';
import IndexPage from 'flarum/components/IndexPage';
import DiscussionList from 'flarum/components/DiscussionList';

export default function addSubscriptionFilter() {
  extend(IndexPage.prototype, 'navItems', function(items) {
    if (app.session.user) {
      const params = this.stickyParams();

      params.filter = 'following';

      items.add('following', LinkButton.component({
        href: app.route('index.filter', params),
        children: app.translator.trans('flarum-subscriptions.forum.index.following_link'),
        icon: 'star'
      }), 50);
    }
  });

  extend(DiscussionList.prototype, 'requestParams', function(params) {
    if (this.props.params.filter === 'following') {
      params.filter.q = (params.filter.q || '') + ' is:following';
    }
  });
}
