/*global s9e*/

import Component from 'flarum/Component';
import avatar from 'flarum/helpers/avatar';
import username from 'flarum/helpers/username';
import DiscussionControls from 'flarum/utils/DiscussionControls';

/**
 * The `ReplyPlaceholder` component displays a placeholder for a reply, which,
 * when clicked, opens the reply composer.
 *
 * ### Props
 *
 * - `discussion`
 */
export default class ReplyPlaceholder extends Component {
  view() {
    if (app.composingReplyTo(this.props.discussion)) {
      return (
        <article className="Post CommentPost editing">
          <header className="Post-header">
            <div className="PostUser">
              <h3>
                {avatar(app.session.user, {className: 'PostUser-avatar'})}
                {username(app.session.user)}
              </h3>
            </div>
          </header>
          <div className="Post-body" config={this.configPreview.bind(this)}/>
        </article>
      );
    }

    const reply = () => {
      DiscussionControls.replyAction.call(this.props.discussion, true);
    };

    return (
      <article className="Post ReplyPlaceholder" onclick={reply}>
        <header className="Post-header">
          {avatar(app.session.user, {className: 'PostUser-avatar'})}{' '}
          {app.translator.trans('core.forum.post_stream.reply_placeholder')}
        </header>
      </article>
    );
  }

  configPreview(element, isInitialized, context) {
    if (isInitialized) return;

    // Every 50ms, if the composer content has changed, then update the post's
    // body with a preview.
    let preview;
    const updateInterval = setInterval(() => {
      const content = app.composer.component.content();

      if (preview === content) return;

      preview = content;

      const anchorToBottom = $(window).scrollTop() + $(window).height() >= $(document).height();

      s9e.TextFormatter.preview(preview || '', element);

      if (anchorToBottom) {
        $(window).scrollTop($(document).height());
      }
    }, 50);

    context.onunload = () => clearInterval(updateInterval);
  }
}
