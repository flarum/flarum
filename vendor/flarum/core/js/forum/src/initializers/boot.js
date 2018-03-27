/*global FastClick*/

import ScrollListener from 'flarum/utils/ScrollListener';
import Pane from 'flarum/utils/Pane';
import Drawer from 'flarum/utils/Drawer';
import mapRoutes from 'flarum/utils/mapRoutes';
import Navigation from 'flarum/components/Navigation';
import HeaderPrimary from 'flarum/components/HeaderPrimary';
import HeaderSecondary from 'flarum/components/HeaderSecondary';
import Composer from 'flarum/components/Composer';
import ModalManager from 'flarum/components/ModalManager';
import AlertManager from 'flarum/components/AlertManager';

/**
 * The `boot` initializer boots up the forum app. It initializes some app
 * globals, mounts components to the page, and begins routing.
 *
 * @param {ForumApp} app
 */
export default function boot(app) {
  // Get the configured default route and update that route's path to be '/'.
  // Push the homepage as the first route, so that the user will always be
  // able to click on the 'back' button to go home, regardless of which page
  // they started on.
  const defaultRoute = app.forum.attribute('defaultRoute');
  let defaultAction = 'index';

  for (const i in app.routes) {
    if (app.routes[i].path === defaultRoute) defaultAction = i;
  }

  app.routes[defaultAction].path = '/';
  app.history.push(defaultAction, app.translator.trans('core.forum.header.back_to_index_tooltip'), '/');

  m.startComputation();

  m.mount(document.getElementById('app-navigation'), Navigation.component({className: 'App-backControl', drawer: true}));
  m.mount(document.getElementById('header-navigation'), Navigation.component());
  m.mount(document.getElementById('header-primary'), HeaderPrimary.component());
  m.mount(document.getElementById('header-secondary'), HeaderSecondary.component());

  app.pane = new Pane(document.getElementById('app'));
  app.drawer = new Drawer();
  app.composer = m.mount(document.getElementById('composer'), Composer.component());
  app.modal = m.mount(document.getElementById('modal'), ModalManager.component());
  app.alerts = m.mount(document.getElementById('alerts'), AlertManager.component());

  const basePath = app.forum.attribute('basePath');
  m.route.mode = 'pathname';
  m.route(
    document.getElementById('content'),
    basePath + '/',
    mapRoutes(app.routes, basePath)
  );

  m.endComputation();

  // Route the home link back home when clicked. We do not want it to register
  // if the user is opening it in a new tab, however.
  $('#home-link').click(e => {
    if (e.ctrlKey || e.metaKey || e.which === 2) return;
    e.preventDefault();
    app.history.home();
    if (app.session.user) {
      app.store.find('users', app.session.user.id());
      m.redraw();
    }
  });

  // Add a class to the body which indicates that the page has been scrolled
  // down.
  new ScrollListener(top => {
    const $app = $('#app');
    const offset = $app.offset().top;

    $app
      .toggleClass('affix', top >= offset)
      .toggleClass('scrolled', top > offset);
  }).start();

  // Initialize FastClick, which makes links and buttons much more responsive on
  // touch devices.
  $(() => {
    FastClick.attach(document.body);

    $('body').addClass('ontouchstart' in window ? 'touch' : 'no-touch');
  });

  app.booted = true;
}
