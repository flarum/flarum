import Component from 'flarum/Component';
import LoadingIndicator from 'flarum/components/LoadingIndicator';
import ItemList from 'flarum/utils/ItemList';
import classList from 'flarum/utils/classList';
import extractText from 'flarum/utils/extractText';
import KeyboardNavigatable from 'flarum/utils/KeyboardNavigatable';
import icon from 'flarum/helpers/icon';
import DiscussionsSearchSource from 'flarum/components/DiscussionsSearchSource';
import UsersSearchSource from 'flarum/components/UsersSearchSource';

/**
 * The `Search` component displays a menu of as-you-type results from a variety
 * of sources.
 *
 * The search box will be 'activated' if the app's current controller implements
 * a `searching` method that returns a truthy value. If this is the case, an 'x'
 * button will be shown next to the search field, and clicking it will call the
 * `clearSearch` method on the controller.
 */
export default class Search extends Component {
  init() {
    /**
     * The value of the search input.
     *
     * @type {Function}
     */
    this.value = m.prop('');

    /**
     * Whether or not the search input has focus.
     *
     * @type {Boolean}
     */
    this.hasFocus = false;

    /**
     * An array of SearchSources.
     *
     * @type {SearchSource[]}
     */
    this.sources = this.sourceItems().toArray();

    /**
     * The number of sources that are still loading results.
     *
     * @type {Integer}
     */
    this.loadingSources = 0;

    /**
     * A list of queries that have been searched for.
     *
     * @type {Array}
     */
    this.searched = [];

    /**
     * The index of the currently-selected <li> in the results list. This can be
     * a unique string (to account for the fact that an item's position may jump
     * around as new results load), but otherwise it will be numeric (the
     * sequential position within the list).
     *
     * @type {String|Integer}
     */
    this.index = 0;
  }

  view() {
    const currentSearch = this.getCurrentSearch();

    // Initialize search input value in the view rather than the constructor so
    // that we have access to app.current.
    if (typeof this.value() === 'undefined') {
      this.value(currentSearch || '');
    }

    return (
      <div className={'Search ' + classList({
        open: this.value() && this.hasFocus,
        focused: this.hasFocus,
        active: !!currentSearch,
        loading: !!this.loadingSources
      })}>
        <div className="Search-input">
          <input className="FormControl"
            type="search"
            placeholder={extractText(app.translator.trans('core.forum.header.search_placeholder'))}
            value={this.value()}
            oninput={m.withAttr('value', this.value)}
            onfocus={() => this.hasFocus = true}
            onblur={() => this.hasFocus = false}/>
          {this.loadingSources
            ? LoadingIndicator.component({size: 'tiny', className: 'Button Button--icon Button--link'})
            : currentSearch
              ? <button className="Search-clear Button Button--icon Button--link" onclick={this.clear.bind(this)}>{icon('times-circle')}</button>
              : ''}
        </div>
        <ul className="Dropdown-menu Search-results">
          {this.value() && this.hasFocus
            ? this.sources.map(source => source.view(this.value()))
            : ''}
        </ul>
      </div>
    );
  }

  config(isInitialized) {
    // Highlight the item that is currently selected.
    this.setIndex(this.getCurrentNumericIndex());

    if (isInitialized) return;

    const search = this;

    this.$('.Search-results')
      .on('mousedown', e => e.preventDefault())
      .on('click', () => this.$('input').blur())

      // Whenever the mouse is hovered over a search result, highlight it.
      .on('mouseenter', '> li:not(.Dropdown-header)', function() {
        search.setIndex(
          search.selectableItems().index(this)
        );
      });

    const $input = this.$('input');

    this.navigator = new KeyboardNavigatable();
    this.navigator
      .onUp(() => this.setIndex(this.getCurrentNumericIndex() - 1, true))
      .onDown(() => this.setIndex(this.getCurrentNumericIndex() + 1, true))
      .onSelect(this.selectResult.bind(this))
      .onCancel(this.clear.bind(this))
      .bindTo($input);

    // Handle input key events on the search input, triggering results to load.
    $input
      .on('input focus', function() {
        const query = this.value.toLowerCase();

        if (!query) return;

        clearTimeout(search.searchTimeout);
        search.searchTimeout = setTimeout(() => {
          if (search.searched.indexOf(query) !== -1) return;

          if (query.length >= 3) {
            search.sources.map(source => {
              if (!source.search) return;

              search.loadingSources++;

              source.search(query).then(() => {
                search.loadingSources--;
                m.redraw();
              });
            });
          }

          search.searched.push(query);
          m.redraw();
        }, 250);
      })

      .on('focus', function() {
        $(this).one('mouseup', e => e.preventDefault()).select();
      });
  }

  /**
   * Get the active search in the app's current controller.
   *
   * @return {String}
   */
  getCurrentSearch() {
    return app.current && typeof app.current.searching === 'function' && app.current.searching();
  }

  /**
   * Navigate to the currently selected search result and close the list.
   */
  selectResult() {
    if (this.value()) {
      m.route(this.getItem(this.index).find('a').attr('href'));
    } else {
      this.clear();
    }

    this.$('input').blur();
  }

  /**
   * Clear the search input and the current controller's active search.
   */
  clear() {
    this.value('');

    if (this.getCurrentSearch()) {
      app.current.clearSearch();
    } else {
      m.redraw();
    }
  }

  /**
   * Build an item list of SearchSources.
   *
   * @return {ItemList}
   */
  sourceItems() {
    const items = new ItemList();

    items.add('discussions', new DiscussionsSearchSource());
    items.add('users', new UsersSearchSource());

    return items;
  }

  /**
   * Get all of the search result items that are selectable.
   *
   * @return {jQuery}
   */
  selectableItems() {
    return this.$('.Search-results > li:not(.Dropdown-header)');
  }

  /**
   * Get the position of the currently selected search result item.
   *
   * @return {Integer}
   */
  getCurrentNumericIndex() {
    return this.selectableItems().index(
      this.getItem(this.index)
    );
  }

  /**
   * Get the <li> in the search results with the given index (numeric or named).
   *
   * @param {String} index
   * @return {DOMElement}
   */
  getItem(index) {
    const $items = this.selectableItems();
    let $item = $items.filter(`[data-index="${index}"]`);

    if (!$item.length) {
      $item = $items.eq(index);
    }

    return $item;
  }

  /**
   * Set the currently-selected search result item to the one with the given
   * index.
   *
   * @param {Integer} index
   * @param {Boolean} scrollToItem Whether or not to scroll the dropdown so that
   *     the item is in view.
   */
  setIndex(index, scrollToItem) {
    const $items = this.selectableItems();
    const $dropdown = $items.parent();

    let fixedIndex = index;
    if (index < 0) {
      fixedIndex = $items.length - 1;
    } else if (index >= $items.length) {
      fixedIndex = 0;
    }

    const $item = $items.removeClass('active').eq(fixedIndex).addClass('active');

    this.index = $item.attr('data-index') || fixedIndex;

    if (scrollToItem) {
      const dropdownScroll = $dropdown.scrollTop();
      const dropdownTop = $dropdown.offset().top;
      const dropdownBottom = dropdownTop + $dropdown.outerHeight();
      const itemTop = $item.offset().top;
      const itemBottom = itemTop + $item.outerHeight();

      let scrollTop;
      if (itemTop < dropdownTop) {
        scrollTop = dropdownScroll - dropdownTop + itemTop - parseInt($dropdown.css('padding-top'), 10);
      } else if (itemBottom > dropdownBottom) {
        scrollTop = dropdownScroll - dropdownBottom + itemBottom + parseInt($dropdown.css('padding-bottom'), 10);
      }

      if (typeof scrollTop !== 'undefined') {
        $dropdown.stop(true).animate({scrollTop}, 100);
      }
    }
  }
}
