import Component from 'flarum/Component';

export default class AutocompleteDropdown extends Component {
  init() {
    this.active = false;
    this.index = 0;
    this.keyWasJustPressed = false;
  }

  view() {
    return (
      <ul className="Dropdown-menu MentionsDropdown">
        {this.props.items.map(item => <li>{item}</li>)}
      </ul>
    );
  }

  show(left, top) {
    this.$().show().css({
      left: left + 'px',
      top: top + 'px'
    });
    this.active = true;
  }

  hide() {
    this.$().hide();
    this.active = false;
  }

  navigate(delta) {
    this.keyWasJustPressed = true;
    this.setIndex(this.index + delta, true);
    clearTimeout(this.keyWasJustPressedTimeout);
    this.keyWasJustPressedTimeout = setTimeout(() => this.keyWasJustPressed = false, 500);
  }

  complete() {
    this.$('li').eq(this.index).find('button').click();
  }

  setIndex(index, scrollToItem) {
    if (this.keyWasJustPressed && !scrollToItem) return;

    const $dropdown = this.$();
    const $items = $dropdown.find('li');
    let rangedIndex = index;

    if (rangedIndex < 0) {
      rangedIndex = $items.length - 1;
    } else if (rangedIndex >= $items.length) {
      rangedIndex = 0;
    }

    this.index = rangedIndex;

    const $item = $items.removeClass('active').eq(rangedIndex).addClass('active');

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
