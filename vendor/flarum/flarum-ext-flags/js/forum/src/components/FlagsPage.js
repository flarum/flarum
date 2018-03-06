import Page from 'flarum/components/Page';

import FlagList from 'flarum/flags/components/FlagList';

/**
 * The `FlagsPage` component shows the flags list. It is only
 * used on mobile devices where the flags dropdown is within the drawer.
 */
export default class FlagsPage extends Page {
  init() {
    super.init();

    app.history.push('flags');

    this.list = new FlagList();
    this.list.load();

    this.bodyClass = 'App--flags';
  }

  view() {
    return <div className="FlagsPage">{this.list.render()}</div>;
  }
}
