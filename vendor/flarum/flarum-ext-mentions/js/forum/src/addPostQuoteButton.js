import { extend } from 'flarum/extend';
import CommentPost from 'flarum/components/CommentPost';

import PostQuoteButton from 'flarum/mentions/components/PostQuoteButton';
import selectedText from 'flarum/mentions/utils/selectedText';

export default function addPostQuoteButton() {
  extend(CommentPost.prototype, 'config', function(original, isInitialized) {
    const post = this.props.post;

    if (isInitialized || post.isHidden() || (app.session.user && !post.discussion().canReply())) return;

    const $postBody = this.$('.Post-body');

    // Wrap the quote button in a wrapper element so that we can render
    // button into it.
    const $container = $('<div class="Post-quoteButtonContainer"></div>');

    const handler = function(e) {
      setTimeout(() => {
        const content = selectedText($postBody);
        if (content) {
          const button = new PostQuoteButton({post, content});
          m.render($container[0], button.render());

          const rects = window.getSelection().getRangeAt(0).getClientRects();
          const firstRect = rects[0];

          if (e.clientY < firstRect.bottom && e.clientX - firstRect.right < firstRect.left - e.clientX) {
            button.showStart(firstRect.left, firstRect.top);
          } else {
            const lastRect = rects[rects.length - 1];
            button.showEnd(lastRect.right, lastRect.bottom);
          }
        }
      }, 1);
    };

    this.$().after($container).on('mouseup', handler);

    if ('ontouchstart' in window) {
      document.addEventListener('selectionchange', handler, false);
    }
  });
}
