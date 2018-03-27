import { extend } from 'flarum/extend';
import AdminNav from 'flarum/components/AdminNav';
import AdminLinkButton from 'flarum/components/AdminLinkButton';

import TagsPage from 'flarum/tags/components/TagsPage';

export default function() {
  app.routes.tags = {path: '/tags', component: TagsPage.component()};

  app.extensionSettings['flarum-tags'] = () => m.route(app.route('tags'));

  extend(AdminNav.prototype, 'items', items => {
    items.add('tags', AdminLinkButton.component({
      href: app.route('tags'),
      icon: 'tags',
      children: app.translator.trans('flarum-tags.admin.nav.tags_button'),
      description: app.translator.trans('flarum-tags.admin.nav.tags_text')
    }));
  });
}
