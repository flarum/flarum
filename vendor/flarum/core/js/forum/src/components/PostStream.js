import Component from 'flarum/Component';
import ScrollListener from 'flarum/utils/ScrollListener';
import PostLoading from 'flarum/components/LoadingPost';
import anchorScroll from 'flarum/utils/anchorScroll';
import mixin from 'flarum/utils/mixin';
import evented from 'flarum/utils/evented';
import ReplyPlaceholder from 'flarum/components/ReplyPlaceholder';
import Button from 'flarum/components/Button';

/**
 * The `PostStream` component displays an infinitely-scrollable wall of posts in
 * a discussion. Posts that have not loaded will be displayed as placeholders.
 *
 * ### Props
 *
 * - `discussion`
 * - `includedPosts`
 */
class PostStream extends Component {
  init() {
    /**
     * The discussion to display the post stream for.
     *
     * @type {Discussion}
     */
    this.discussion = this.props.discussion;

    /**
     * Whether or not the infinite-scrolling auto-load functionality is
     * disabled.
     *
     * @type {Boolean}
     */
    this.paused = false;

    this.scrollListener = new ScrollListener(this.onscroll.bind(this));
    this.loadPageTimeouts = {};
    this.pagesLoading = 0;

    this.show(this.props.includedPosts);
  }

  /**
   * Load and scroll to a post with a certain number.
   *
   * @param {Integer|String} number The post number to go to. If 'reply', go to
   *     the last post and scroll the reply preview into view.
   * @param {Boolean} noAnimation
   * @return {Promise}
   */
  goToNumber(number, noAnimation) {
    // If we want to go to the reply preview, then we will go to the end of the
    // discussion and then scroll to the very bottom of the page.
    if (number === 'reply') {
      return this.goToLast().then(() => {
        $('html,body').stop(true).animate({
          scrollTop: $(document).height() - $(window).height()
        }, 'fast', () => {
          this.flashItem(this.$('.PostStream-item:last-child'));
        });
      });
    }

    this.paused = true;

    const promise = this.loadNearNumber(number);

    m.redraw(true);

    return promise.then(() => {
      m.redraw(true);

      this.scrollToNumber(number, noAnimation).done(this.unpause.bind(this));
    });
  }

  /**
   * Load and scroll to a certain index within the discussion.
   *
   * @param {Integer} index
   * @param {Boolean} backwards Whether or not to load backwards from the given
   *     index.
   * @param {Boolean} noAnimation
   * @return {Promise}
   */
  goToIndex(index, backwards, noAnimation) {
    this.paused = true;

    const promise = this.loadNearIndex(index);

    m.redraw(true);

    return promise.then(() => {
      anchorScroll(this.$('.PostStream-item:' + (backwards ? 'last' : 'first')), () => m.redraw(true));

      this.scrollToIndex(index, noAnimation, backwards).done(this.unpause.bind(this));
    });
  }

  /**
   * Load and scroll up to the first post in the discussion.
   *
   * @return {Promise}
   */
  goToFirst() {
    return this.goToIndex(0);
  }

  /**
   * Load and scroll down to the last post in the discussion.
   *
   * @return {Promise}
   */
  goToLast() {
    return this.goToIndex(this.count() - 1, true);
  }

  /**
   * Update the stream so that it loads and includes the latest posts in the
   * discussion, if the end is being viewed.
   *
   * @public
   */
  update() {
    if (!this.viewingEnd) return;

    this.visibleEnd = this.count();

    this.loadRange(this.visibleStart, this.visibleEnd).then(() => m.redraw());
  }

  /**
   * Get the total number of posts in the discussion.
   *
   * @return {Integer}
   */
  count() {
    return this.discussion.postIds().length;
  }

  /**
   * Make sure that the given index is not outside of the possible range of
   * indexes in the discussion.
   *
   * @param {Integer} index
   * @protected
   */
  sanitizeIndex(index) {
    return Math.max(0, Math.min(this.count(), index));
  }

  /**
   * Set up the stream with the given array of posts.
   *
   * @param {Post[]} posts
   */
  show(posts) {
    this.visibleStart = posts.length ? this.discussion.postIds().indexOf(posts[0].id()) : 0;
    this.visibleEnd = this.visibleStart + posts.length;
  }

  /**
   * Reset the stream so that a specific range of posts is displayed. If a range
   * is not specified, the first page of posts will be displayed.
   *
   * @param {Integer} [start]
   * @param {Integer} [end]
   */
  reset(start, end) {
    this.visibleStart = start || 0;
    this.visibleEnd = this.sanitizeIndex(end || this.constructor.loadCount);
  }

  /**
   * Get the visible page of posts.
   *
   * @return {Post[]}
   */
  posts() {
    return this.discussion.postIds()
      .slice(this.visibleStart, this.visibleEnd)
      .map(id => {
        const post = app.store.getById('posts', id);

        return post && post.discussion() && typeof post.canEdit() !== 'undefined' ? post : null;
      });
  }

  view() {
    function fadeIn(element, isInitialized, context) {
      if (!context.fadedIn) $(element).hide().fadeIn();
      context.fadedIn = true;
    }

    let lastTime;

    this.visibleEnd = this.sanitizeIndex(this.visibleEnd);
    this.viewingEnd = this.visibleEnd === this.count();

    const posts = this.posts();
    const postIds = this.discussion.postIds();

    const items = posts.map((post, i) => {
      let content;
      const attrs = {'data-index': this.visibleStart + i};

      if (post) {
        const time = post.time();
        const PostComponent = app.postComponents[post.contentType()];
        content = PostComponent ? PostComponent.component({post}) : '';

        attrs.key = 'post' + post.id();
        attrs.config = fadeIn;
        attrs['data-time'] = time.toISOString();
        attrs['data-number'] = post.number();
        attrs['data-id'] = post.id();
        attrs['data-type'] = post.contentType();

        // If the post before this one was more than 4 hours ago, we will
        // display a 'time gap' indicating how long it has been in between
        // the posts.
        const dt = time - lastTime;

        if (dt > 1000 * 60 * 60 * 24 * 4) {
          content = [
            <div className="PostStream-timeGap">
              <span>{app.translator.trans('core.forum.post_stream.time_lapsed_text', {period: moment.duration(dt).humanize()})}</span>
            </div>,
            content
          ];
        }

        lastTime = time;
      } else {
        attrs.key = 'post' + postIds[this.visibleStart + i];

        content = PostLoading.component();
      }

      return <div className="PostStream-item" {...attrs}>{content}</div>;
    });

    if (!this.viewingEnd && posts[this.visibleEnd - this.visibleStart - 1]) {
      items.push(
        <div className="PostStream-loadMore" key="loadMore">
          <Button className="Button" onclick={this.loadNext.bind(this)}>
            {app.translator.trans('core.forum.post_stream.load_more_button')}
          </Button>
        </div>
      );
    }

    // If we're viewing the end of the discussion, the user can reply, and
    // is not already doing so, then show a 'write a reply' placeholder.
    if (this.viewingEnd && (!app.session.user || this.discussion.canReply())) {
      items.push(
        <div className="PostStream-item" key="reply">
          {ReplyPlaceholder.component({discussion: this.discussion})}
        </div>
      );
    }

    return (
      <div className="PostStream">
        {items}
      </div>
    );
  }

  config(isInitialized, context) {
    if (isInitialized) return;

    // This is wrapped in setTimeout due to the following Mithril issue:
    // https://github.com/lhorie/mithril.js/issues/637
    setTimeout(() => this.scrollListener.start());

    context.onunload = () => {
      this.scrollListener.stop();
      clearTimeout(this.calculatePositionTimeout);
    };
  }

  /**
   * When the window is scrolled, check if either extreme of the post stream is
   * in the viewport, and if so, trigger loading the next/previous page.
   *
   * @param {Integer} top
   */
  onscroll(top) {
    if (this.paused) return;

    const marginTop = this.getMarginTop();
    const viewportHeight = $(window).height() - marginTop;
    const viewportTop = top + marginTop;
    const loadAheadDistance = 300;

    if (this.visibleStart > 0) {
      const $item = this.$('.PostStream-item[data-index=' + this.visibleStart + ']');

      if ($item.length && $item.offset().top > viewportTop - loadAheadDistance) {
        this.loadPrevious();
      }
    }

    if (this.visibleEnd < this.count()) {
      const $item = this.$('.PostStream-item[data-index=' + (this.visibleEnd - 1) + ']');

      if ($item.length && $item.offset().top + $item.outerHeight(true) < viewportTop + viewportHeight + loadAheadDistance) {
        this.loadNext();
      }
    }

    // Throttle calculation of our position (start/end numbers of posts in the
    // viewport) to 100ms.
    clearTimeout(this.calculatePositionTimeout);
    this.calculatePositionTimeout = setTimeout(this.calculatePosition.bind(this), 100);
  }

  /**
   * Load the next page of posts.
   */
  loadNext() {
    const start = this.visibleEnd;
    const end = this.visibleEnd = this.sanitizeIndex(this.visibleEnd + this.constructor.loadCount);

    // Unload the posts which are two pages back from the page we're currently
    // loading.
    const twoPagesAway = start - this.constructor.loadCount * 2;
    if (twoPagesAway > this.visibleStart && twoPagesAway >= 0) {
      this.visibleStart = twoPagesAway + this.constructor.loadCount + 1;

      if (this.loadPageTimeouts[twoPagesAway]) {
        clearTimeout(this.loadPageTimeouts[twoPagesAway]);
        this.loadPageTimeouts[twoPagesAway] = null;
        this.pagesLoading--;
      }
    }

    this.loadPage(start, end);
  }

  /**
   * Load the previous page of posts.
   */
  loadPrevious() {
    const end = this.visibleStart;
    const start = this.visibleStart = this.sanitizeIndex(this.visibleStart - this.constructor.loadCount);

    // Unload the posts which are two pages back from the page we're currently
    // loading.
    const twoPagesAway = start + this.constructor.loadCount * 2;
    if (twoPagesAway < this.visibleEnd && twoPagesAway <= this.count()) {
      this.visibleEnd = twoPagesAway;

      if (this.loadPageTimeouts[twoPagesAway]) {
        clearTimeout(this.loadPageTimeouts[twoPagesAway]);
        this.loadPageTimeouts[twoPagesAway] = null;
        this.pagesLoading--;
      }
    }

    this.loadPage(start, end, true);
  }

  /**
   * Load a page of posts into the stream and redraw.
   *
   * @param {Integer} start
   * @param {Integer} end
   * @param {Boolean} backwards
   */
  loadPage(start, end, backwards) {
    const redraw = () => {
      if (start < this.visibleStart || end > this.visibleEnd) return;

      const anchorIndex = backwards ? this.visibleEnd - 1 : this.visibleStart;
      anchorScroll(`.PostStream-item[data-index="${anchorIndex}"]`, () => m.redraw(true));

      this.unpause();
    };
    redraw();

    this.loadPageTimeouts[start] = setTimeout(() => {
      this.loadRange(start, end).then(() => {
        redraw();
        this.pagesLoading--;
      });
      this.loadPageTimeouts[start] = null;
    }, this.pagesLoading ? 1000 : 0);

    this.pagesLoading++;
  }

  /**
   * Load and inject the specified range of posts into the stream, without
   * clearing it.
   *
   * @param {Integer} start
   * @param {Integer} end
   * @return {Promise}
   */
  loadRange(start, end) {
    const loadIds = [];
    const loaded = [];

    this.discussion.postIds().slice(start, end).forEach(id => {
      const post = app.store.getById('posts', id);

      if (post && post.discussion() && typeof post.canEdit() !== 'undefined') {
        loaded.push(post);
      } else {
        loadIds.push(id);
      }
    });

    return loadIds.length
      ? app.store.find('posts', loadIds)
      : m.deferred().resolve(loaded).promise;
  }

  /**
   * Clear the stream and load posts near a certain number. Returns a promise.
   * If the post with the given number is already loaded, the promise will be
   * resolved immediately.
   *
   * @param {Integer} number
   * @return {Promise}
   */
  loadNearNumber(number) {
    if (this.posts().some(post => post && Number(post.number()) === Number(number))) {
      return m.deferred().resolve().promise;
    }

    this.reset();

    return app.store.find('posts', {
      filter: {discussion: this.discussion.id()},
      page: {near: number}
    }).then(this.show.bind(this));
  }

  /**
   * Clear the stream and load posts near a certain index. A page of posts
   * surrounding the given index will be loaded. Returns a promise. If the given
   * index is already loaded, the promise will be resolved immediately.
   *
   * @param {Integer} index
   * @return {Promise}
   */
  loadNearIndex(index) {
    if (index >= this.visibleStart && index <= this.visibleEnd) {
      return m.deferred().resolve().promise;
    }

    const start = this.sanitizeIndex(index - this.constructor.loadCount / 2);
    const end = start + this.constructor.loadCount;

    this.reset(start, end);

    return this.loadRange(start, end).then(this.show.bind(this));
  }

  /**
   * Work out which posts (by number) are currently visible in the viewport, and
   * fire an event with the information.
   */
  calculatePosition() {
    const marginTop = this.getMarginTop();
    const $window = $(window);
    const viewportHeight = $window.height() - marginTop;
    const scrollTop = $window.scrollTop() + marginTop;
    let startNumber;
    let endNumber;

    this.$('.PostStream-item').each(function() {
      const $item = $(this);
      const top = $item.offset().top;
      const height = $item.outerHeight(true);

      if (top + height > scrollTop) {
        if (!startNumber) {
          startNumber = endNumber = $item.data('number');
        }

        if (top + height < scrollTop + viewportHeight) {
          if ($item.data('number')) {
            endNumber = $item.data('number');
          }
        } else return false;
      }
    });

    if (startNumber) {
      this.trigger('positionChanged', startNumber || 1, endNumber);
    }
  }

  /**
   * Get the distance from the top of the viewport to the point at which we
   * would consider a post to be the first one visible.
   *
   * @return {Integer}
   */
  getMarginTop() {
    return this.$() && $('#header').outerHeight() + parseInt(this.$().css('margin-top'), 10);
  }

  /**
   * Scroll down to a certain post by number and 'flash' it.
   *
   * @param {Integer} number
   * @param {Boolean} noAnimation
   * @return {jQuery.Deferred}
   */
  scrollToNumber(number, noAnimation) {
    const $item = this.$(`.PostStream-item[data-number=${number}]`);

    return this.scrollToItem($item, noAnimation).done(this.flashItem.bind(this, $item));
  }

  /**
   * Scroll down to a certain post by index.
   *
   * @param {Integer} index
   * @param {Boolean} noAnimation
   * @param {Boolean} bottom Whether or not to scroll to the bottom of the post
   *     at the given index, instead of the top of it.
   * @return {jQuery.Deferred}
   */
  scrollToIndex(index, noAnimation, bottom) {
    const $item = this.$(`.PostStream-item[data-index=${index}]`);

    return this.scrollToItem($item, noAnimation, true, bottom);
  }

  /**
   * Scroll down to the given post.
   *
   * @param {jQuery} $item
   * @param {Boolean} noAnimation
   * @param {Boolean} force Whether or not to force scrolling to the item, even
   *     if it is already in the viewport.
   * @param {Boolean} bottom Whether or not to scroll to the bottom of the post
   *     at the given index, instead of the top of it.
   * @return {jQuery.Deferred}
   */
  scrollToItem($item, noAnimation, force, bottom) {
    const $container = $('html, body').stop(true);

    if ($item.length) {
      const itemTop = $item.offset().top - this.getMarginTop();
      const itemBottom = $item.offset().top + $item.height();
      const scrollTop = $(document).scrollTop();
      const scrollBottom = scrollTop + $(window).height();

      // If the item is already in the viewport, we may not need to scroll.
      // If we're scrolling to the bottom of an item, then we'll make sure the
      // bottom will line up with the top of the composer.
      if (force || itemTop < scrollTop || itemBottom > scrollBottom) {
        const top = bottom
          ? itemBottom - $(window).height() + app.composer.computedHeight()
          : ($item.is(':first-child') ? 0 : itemTop);

        if (noAnimation) {
          $container.scrollTop(top);
        } else if (top !== scrollTop) {
          $container.animate({scrollTop: top}, 'fast');
        }
      }
    }

    return $container.promise();
  }

  /**
   * 'Flash' the given post, drawing the user's attention to it.
   *
   * @param {jQuery} $item
   */
  flashItem($item) {
    $item.addClass('flash').one('animationend webkitAnimationEnd', () => $item.removeClass('flash'));
  }

  /**
   * Resume the stream's ability to auto-load posts on scroll.
   */
  unpause() {
    this.paused = false;
    this.scrollListener.update(true);
    this.trigger('unpaused');
  }
}

/**
 * The number of posts to load per page.
 *
 * @type {Integer}
 */
PostStream.loadCount = 20;

Object.assign(PostStream.prototype, evented);

export default PostStream;
