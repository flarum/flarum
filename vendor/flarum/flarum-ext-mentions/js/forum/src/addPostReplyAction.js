import { extend } from 'flarum/extend';
import Button from 'flarum/components/Button';
import CommentPost from 'flarum/components/CommentPost';

import reply from 'flarum/mentions/utils/reply';

export default function () {
  extend(CommentPost.prototype, 'actionItems', function (items) {

    const post = this.props.post;

    if (post.isHidden() || (app.session.user && !post.discussion().canReply())) return;

    items.add('reply',
      Button.component({
        className: 'Button Button--link',
        children: app.translator.trans('flarum-mentions.forum.post.reply_link'),
        onclick: () => reply(post)
      })
    );
  });
}
