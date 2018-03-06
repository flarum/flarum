import Component from 'flarum/Component';
import Button from 'flarum/components/Button';
import LogInModal from 'flarum/components/LogInModal';
import SignUpModal from 'flarum/components/SignUpModal';
import SessionDropdown from 'flarum/components/SessionDropdown';
import SelectDropdown from 'flarum/components/SelectDropdown';
import NotificationsDropdown from 'flarum/components/NotificationsDropdown';
import ItemList from 'flarum/utils/ItemList';
import listItems from 'flarum/helpers/listItems';

/**
 * The `HeaderSecondary` component displays secondary header controls, such as
 * the search box and the user menu. On the default skin, these are shown on the
 * right side of the header.
 */
export default class HeaderSecondary extends Component {
  view() {
    return (
      <ul className="Header-controls">
        {listItems(this.items().toArray())}
      </ul>
    );
  }

  config(isInitialized, context) {
    // Since this component is 'above' the content of the page (that is, it is a
    // part of the global UI that persists between routes), we will flag the DOM
    // to be retained across route changes.
    context.retain = true;
  }

  /**
   * Build an item list for the controls.
   *
   * @return {ItemList}
   */
  items() {
    const items = new ItemList();

    items.add('search', app.search.render(), 30);

    if (app.forum.attribute("showLanguageSelector") && Object.keys(app.data.locales).length > 1) {
      const locales = [];

      for (const locale in app.data.locales) {
        locales.push(Button.component({
          active: app.data.locale === locale,
          children: app.data.locales[locale],
          icon: app.data.locale === locale ? 'check' : true,
          onclick: () => {
            if (app.session.user) {
              app.session.user.savePreferences({locale}).then(() => window.location.reload());
            } else {
              document.cookie = `locale=${locale}; path=/; expires=Tue, 19 Jan 2038 03:14:07 GMT`;
              window.location.reload();
            }
          }
        }));
      }

      items.add('locale', SelectDropdown.component({
        children: locales,
        buttonClassName: 'Button Button--link'
      }), 20);
    }

    if (app.session.user) {
      items.add('notifications', NotificationsDropdown.component(), 10);
      items.add('session', SessionDropdown.component(), 0);
    } else {
      if (app.forum.attribute('allowSignUp')) {
        items.add('signUp',
          Button.component({
            children: app.translator.trans('core.forum.header.sign_up_link'),
            className: 'Button Button--link',
            onclick: () => app.modal.show(new SignUpModal())
          }), 10
        );
      }

      items.add('logIn',
        Button.component({
          children: app.translator.trans('core.forum.header.log_in_link'),
          className: 'Button Button--link',
          onclick: () => app.modal.show(new LogInModal())
        }), 0
      );
    }

    return items;
  }
}
