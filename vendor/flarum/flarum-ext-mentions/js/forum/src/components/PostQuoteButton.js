import Button from 'flarum/components/Button';
import extract from 'flarum/utils/extract';

import reply from 'flarum/mentions/utils/reply';

export default class PostQuoteButton extends Button {
  view() {
    const post = extract(this.props, 'post');
    const content = extract(this.props, 'content');

    this.props.className = 'Button PostQuoteButton';
    this.props.icon = 'quote-left';
    this.props.children = app.translator.trans('flarum-mentions.forum.post.quote_button');
    this.props.onclick = () => {
      this.hide();
      reply(post, content);
    };
    this.props.onmousedown = (e) => e.stopPropagation();

    return super.view();
  }

  config(isInitialized) {
    if (isInitialized) return;

    $(document).on('mousedown', this.hide.bind(this));
  }

  showStart(left, top) {
    const $this = this.$();

    $this.show()
      .css('left', left)
      .css('top', $(window).scrollTop() + top - $this.outerHeight() - 5);
  }

  showEnd(right, bottom) {
    const $this = this.$();

    $this.show()
      .css('left', right - $this.outerWidth())
      .css('top', $(window).scrollTop() + bottom + 5)
  }

  hide() {
    this.$().hide();
  }
}
