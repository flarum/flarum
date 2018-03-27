import { extend } from 'flarum/extend';
import Page from 'flarum/components/Page';
import ItemList from 'flarum/utils/ItemList';
import listItems from 'flarum/helpers/listItems';
import icon from 'flarum/helpers/icon';
import DiscussionList from 'flarum/components/DiscussionList';
import WelcomeHero from 'flarum/components/WelcomeHero';
import DiscussionComposer from 'flarum/components/DiscussionComposer';
import LogInModal from 'flarum/components/LogInModal';
import DiscussionPage from 'flarum/components/DiscussionPage';
import Dropdown from 'flarum/components/Dropdown';
import Button from 'flarum/components/Button';
import LinkButton from 'flarum/components/LinkButton';
import SelectDropdown from 'flarum/components/SelectDropdown';

/**
 * The `IndexPage` component displays the index page, including the welcome
 * hero, the sidebar, and the discussion list.
 */
export default class IndexPage extends Page {
  init() {
    super.init();

    // If the user is returning from a discussion page, then take note of which
    // discussion they have just visited. After the view is rendered, we will
    // scroll down so that this discussion is in view.
    if (app.previous instanceof DiscussionPage) {
      this.lastDiscussion = app.previous.discussion;
    }

    // If the user is coming from the discussion list, then they have either
    // just switched one of the parameters (filter, sort, search) or they
    // probably want to refresh the results. We will clear the discussion list
    // cache so that results are reloaded.
    if (app.previous instanceof IndexPage) {
      app.cache.discussionList = null;
    }

    const params = this.params();

    if (app.cache.discussionList) {
      // Compare the requested parameters (sort, search query) to the ones that
      // are currently present in the cached discussion list. If they differ, we
      // will clear the cache and set up a new discussion list component with
      // the new parameters.
      Object.keys(params).some(key => {
        if (app.cache.discussionList.props.params[key] !== params[key]) {
          app.cache.discussionList = null;
          return true;
        }
      });
    }

    if (!app.cache.discussionList) {
      app.cache.discussionList = new DiscussionList({params});
    }

    app.history.push('index', app.translator.trans('core.forum.header.back_to_index_tooltip'));

    this.bodyClass = 'App--index';
  }

  onunload() {
    // Save the scroll position so we can restore it when we return to the
    // discussion list.
    app.cache.scrollTop = $(window).scrollTop();
  }

  view() {
    return (
      <div className="IndexPage">
        {this.hero()}
        <div className="container">
          <nav className="IndexPage-nav sideNav">
            <ul>{listItems(this.sidebarItems().toArray())}</ul>
          </nav>
          <div className="IndexPage-results sideNavOffset">
            <div className="IndexPage-toolbar">
              <ul className="IndexPage-toolbar-view">{listItems(this.viewItems().toArray())}</ul>
              <ul className="IndexPage-toolbar-action">{listItems(this.actionItems().toArray())}</ul>
            </div>
            {app.cache.discussionList.render()}
          </div>
        </div>
      </div>
    );
  }

  config(isInitialized, context) {
    super.config(...arguments);

    if (isInitialized) return;

    extend(context, 'onunload', () => $('#app').css('min-height', ''));

    app.setTitle('');
    app.setTitleCount(0);

    // Work out the difference between the height of this hero and that of the
    // previous hero. Maintain the same scroll position relative to the bottom
    // of the hero so that the sidebar doesn't jump around.
    const oldHeroHeight = app.cache.heroHeight;
    const heroHeight = app.cache.heroHeight = this.$('.Hero').outerHeight();
    const scrollTop = app.cache.scrollTop;

    $('#app').css('min-height', $(window).height() + heroHeight);

    // Scroll to the remembered position. We do this after a short delay so that
    // it happens after the browser has done its own "back button" scrolling,
    // which isn't right. https://github.com/flarum/core/issues/835
    const scroll = () => $(window).scrollTop(scrollTop - oldHeroHeight + heroHeight);
    scroll();
    setTimeout(scroll, 1);

    // If we've just returned from a discussion page, then the constructor will
    // have set the `lastDiscussion` property. If this is the case, we want to
    // scroll down to that discussion so that it's in view.
    if (this.lastDiscussion) {
      const $discussion = this.$(`.DiscussionListItem[data-id="${this.lastDiscussion.id()}"]`);

      if ($discussion.length) {
        const indexTop = $('#header').outerHeight();
        const indexBottom = $(window).height();
        const discussionTop = $discussion.offset().top;
        const discussionBottom = discussionTop + $discussion.outerHeight();

        if (discussionTop < scrollTop + indexTop || discussionBottom > scrollTop + indexBottom) {
          $(window).scrollTop(discussionTop - indexTop);
        }
      }
    }
  }

  /**
   * Get the component to display as the hero.
   *
   * @return {MithrilComponent}
   */
  hero() {
    return WelcomeHero.component();
  }

  /**
   * Build an item list for the sidebar of the index page. By default this is a
   * "New Discussion" button, and then a DropdownSelect component containing a
   * list of navigation items.
   *
   * @return {ItemList}
   */
  sidebarItems() {
    const items = new ItemList();
    const canStartDiscussion = app.forum.attribute('canStartDiscussion') || !app.session.user;

    items.add('newDiscussion',
      Button.component({
        children: app.translator.trans(canStartDiscussion ? 'core.forum.index.start_discussion_button' : 'core.forum.index.cannot_start_discussion_button'),
        icon: 'edit',
        className: 'Button Button--primary IndexPage-newDiscussion',
        itemClassName: 'App-primaryControl',
        onclick: this.newDiscussion.bind(this),
        disabled: !canStartDiscussion
      })
    );

    items.add('nav',
      SelectDropdown.component({
        children: this.navItems(this).toArray(),
        buttonClassName: 'Button',
        className: 'App-titleControl'
      })
    );

    return items;
  }

  /**
   * Build an item list for the navigation in the sidebar of the index page. By
   * default this is just the 'All Discussions' link.
   *
   * @return {ItemList}
   */
  navItems() {
    const items = new ItemList();
    const params = this.stickyParams();

    items.add('allDiscussions',
      LinkButton.component({
        href: app.route('index', params),
        children: app.translator.trans('core.forum.index.all_discussions_link'),
        icon: 'comments-o'
      }),
      100
    );

    return items;
  }

  /**
   * Build an item list for the part of the toolbar which is concerned with how
   * the results are displayed. By default this is just a select box to change
   * the way discussions are sorted.
   *
   * @return {ItemList}
   */
  viewItems() {
    const items = new ItemList();
    const sortMap = app.cache.discussionList.sortMap();

    const sortOptions = {};
    for (const i in sortMap) {
      sortOptions[i] = app.translator.trans('core.forum.index_sort.' + i + '_button');
    }

    items.add('sort',
      Dropdown.component({
        buttonClassName: 'Button',
        label: sortOptions[this.params().sort] || Object.keys(sortMap).map(key => sortOptions[key])[0],
        children: Object.keys(sortOptions).map(value => {
          const label = sortOptions[value];
          const active = (this.params().sort || Object.keys(sortMap)[0]) === value;

          return Button.component({
            children: label,
            icon: active ? 'check' : true,
            onclick: this.changeSort.bind(this, value),
            active: active,
          })
        }),
      })
    );

    return items;
  }

  /**
   * Build an item list for the part of the toolbar which is about taking action
   * on the results. By default this is just a "mark all as read" button.
   *
   * @return {ItemList}
   */
  actionItems() {
    const items = new ItemList();

    items.add('refresh',
      Button.component({
        title: app.translator.trans('core.forum.index.refresh_tooltip'),
        icon: 'refresh',
        className: 'Button Button--icon',
        onclick: () => {
          app.cache.discussionList.refresh();
          if (app.session.user) {
            app.store.find('users', app.session.user.id());
            m.redraw();
          }
        }
      })
    );

    if (app.session.user) {
      items.add('markAllAsRead',
        Button.component({
          title: app.translator.trans('core.forum.index.mark_all_as_read_tooltip'),
          icon: 'check',
          className: 'Button Button--icon',
          onclick: this.markAllAsRead.bind(this)
        })
      );
    }

    return items;
  }

  /**
   * Return the current search query, if any. This is implemented to activate
   * the search box in the header.
   *
   * @see Search
   * @return {String}
   */
  searching() {
    return this.params().q;
  }

  /**
   * Redirect to the index page without a search filter. This is called when the
   * 'x' is clicked in the search box in the header.
   *
   * @see Search
   */
  clearSearch() {
    const params = this.params();
    delete params.q;

    m.route(app.route(this.props.routeName, params));
  }

  /**
   * Redirect to the index page using the given sort parameter.
   *
   * @param {String} sort
   */
  changeSort(sort) {
    const params = this.params();

    if (sort === Object.keys(app.cache.discussionList.sortMap())[0]) {
      delete params.sort;
    } else {
      params.sort = sort;
    }

    m.route(app.route(this.props.routeName, params));
  }

  /**
   * Get URL parameters that stick between filter changes.
   *
   * @return {Object}
   */
  stickyParams() {
    return {
      sort: m.route.param('sort'),
      q: m.route.param('q')
    };
  }

  /**
   * Get parameters to pass to the DiscussionList component.
   *
   * @return {Object}
   */
  params() {
    const params = this.stickyParams();

    params.filter = m.route.param('filter');

    return params;
  }

  /**
   * Log the user in and then open the composer for a new discussion.
   *
   * @return {Promise}
   */
  newDiscussion() {
    const deferred = m.deferred();

    if (app.session.user) {
      this.composeNewDiscussion(deferred);
    } else {
      app.modal.show(
        new LogInModal({
          onlogin: this.composeNewDiscussion.bind(this, deferred)
        })
      );
    }

    return deferred.promise;
  }

  /**
   * Initialize the composer for a new discussion.
   *
   * @param {Deferred} deferred
   * @return {Promise}
   */
  composeNewDiscussion(deferred) {
    const component = new DiscussionComposer({user: app.session.user});

    app.composer.load(component);
    app.composer.show();

    deferred.resolve(component);

    return deferred.promise;
  }

  /**
   * Mark all discussions as read.
   *
   * @return void
   */
  markAllAsRead() {
    const confirmation = confirm(app.translator.trans('core.forum.index.mark_all_as_read_confirmation'));

    if (confirmation) {
      app.session.user.save({readTime: new Date()});
    }
  }
}
