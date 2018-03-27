import { extend } from 'flarum/extend';
import app from 'flarum/app';
import CommentPost from 'flarum/components/CommentPost';
import punctuateSeries from 'flarum/helpers/punctuateSeries';
import username from 'flarum/helpers/username';
import icon from 'flarum/helpers/icon';

import PostLikesModal from 'flarum/likes/components/PostLikesModal';

export default function() {
  extend(CommentPost.prototype, 'footerItems', function(items) {
    const post = this.props.post;
    const likes = post.likes();

    if (likes && likes.length) {
      const limit = 4;
      const overLimit = likes.length > limit;

      // Construct a list of names of users who have liked this post. Make sure the
      // current user is first in the list, and cap a maximum of 4 items.
      const names = likes.sort(a => a === app.session.user ? -1 : 1)
        .slice(0, overLimit ? limit - 1 : limit)
        .map(user => {
          return (
            <a href={app.route.user(user)} config={m.route}>
              {user === app.session.user ? app.translator.trans('flarum-likes.forum.post.you_text') : username(user)}
            </a>
          );
        });

      // If there are more users that we've run out of room to display, add a "x
      // others" name to the end of the list. Clicking on it will display a modal
      // with a full list of names.
      if (overLimit) {
        const count = likes.length - names.length;

        names.push(
          <a href="#" onclick={e => {
            e.preventDefault();
            app.modal.show(new PostLikesModal({post}));
          }}>
            {app.translator.transChoice('flarum-likes.forum.post.others_link', count, {count})}
          </a>
        );
      }

      items.add('liked', (
        <div className="Post-likedBy">
          {icon('thumbs-o-up')}
          {app.translator.transChoice('flarum-likes.forum.post.liked_by' + (likes[0] === app.session.user ? '_self' : '') + '_text', names.length, {
            count: names.length,
            users: punctuateSeries(names)
          })}
        </div>
      ));
    }
  });
}
