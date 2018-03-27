import { override } from 'flarum/extend';
import app from 'flarum/app';
import Post from 'flarum/models/Post';

import addComposerAutocomplete from 'flarum/emoji/addComposerAutocomplete';

app.initializers.add('flarum-emoji', () => {
  // After typing ':' in the composer, show a dropdown suggesting a bunch of
  // emoji that the user could use.
  addComposerAutocomplete();
});
