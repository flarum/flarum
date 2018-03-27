import app from 'flarum/app';
import Model from 'flarum/Model';

import Flag from 'flarum/flags/models/Flag';
import FlagsPage from 'flarum/flags/components/FlagsPage';
import addFlagControl from 'flarum/flags/addFlagControl';
import addFlagsDropdown from 'flarum/flags/addFlagsDropdown';
import addFlagsToPosts from 'flarum/flags/addFlagsToPosts';

app.initializers.add('flarum-flags', () => {
  app.store.models.posts.prototype.flags = Model.hasMany('flags');
  app.store.models.posts.prototype.canFlag = Model.attribute('canFlag');

  app.store.models.flags = Flag;

  app.routes.flags = {path: '/flags', component: <FlagsPage/>};

  addFlagControl();
  addFlagsDropdown();
  addFlagsToPosts();
});
