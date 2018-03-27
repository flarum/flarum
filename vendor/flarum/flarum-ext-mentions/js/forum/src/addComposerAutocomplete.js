/*global getCaretCoordinates*/

import { extend } from 'flarum/extend';
import ComposerBody from 'flarum/components/ComposerBody';
import avatar from 'flarum/helpers/avatar';
import usernameHelper from 'flarum/helpers/username';
import highlight from 'flarum/helpers/highlight';
import KeyboardNavigatable from 'flarum/utils/KeyboardNavigatable';
import { truncate } from 'flarum/utils/string';

import AutocompleteDropdown from 'flarum/mentions/components/AutocompleteDropdown';

export default function addComposerAutocomplete() {
  extend(ComposerBody.prototype, 'config', function(original, isInitialized) {
    if (isInitialized) return;

    const composer = this;
    const $container = $('<div class="ComposerBody-mentionsDropdownContainer"></div>');
    const dropdown = new AutocompleteDropdown({items: []});
    const $textarea = this.$('textarea').wrap('<div class="ComposerBody-mentionsWrapper"></div>');
    const searched = [];
    let mentionStart;
    let typed;
    let searchTimeout;

    const applySuggestion = function(replacement) {
      const insert = replacement + ' ';

      const content = composer.content();
      composer.editor.setValue(content.substring(0, mentionStart - 1) + insert + content.substr($textarea[0].selectionStart));

      const index = mentionStart - 1 + insert.length;
      composer.editor.setSelectionRange(index, index);

      dropdown.hide();
    };

    this.navigator = new KeyboardNavigatable();
    this.navigator
      .when(() => dropdown.active)
      .onUp(() => dropdown.navigate(-1))
      .onDown(() => dropdown.navigate(1))
      .onSelect(dropdown.complete.bind(dropdown))
      .onCancel(dropdown.hide.bind(dropdown))
      .bindTo($textarea);

    $textarea
      .after($container)
      .on('click keyup', function(e) {
        // Up, down, enter, tab, escape, left, right.
        if ([9, 13, 27, 40, 38, 37, 39].indexOf(e.which) !== -1) return;

        const cursor = this.selectionStart;

        if (this.selectionEnd - cursor > 0) return;

        // Search backwards from the cursor for an '@' symbol, without any
        // intervening whitespace. If we find one, we will want to show the
        // autocomplete dropdown!
        const value = this.value;
        mentionStart = 0;
        for (let i = cursor - 1; i >= 0; i--) {
          const character = value.substr(i, 1);
          if (/\s/.test(character)) break;
          if (character === '@') {
            mentionStart = i + 1;
            break;
          }
        }

        dropdown.hide();
        dropdown.active = false;

        if (mentionStart) {
          typed = value.substring(mentionStart, cursor).toLowerCase();

          const makeSuggestion = function(user, replacement, content, className = '') {
            const username = usernameHelper(user);
            if (typed) {
              username.children[0] = highlight(username.children[0], typed);
            }

            return (
              <button className={'PostPreview ' + className}
                onclick={() => applySuggestion(replacement)}
                onmouseenter={function() {
                  dropdown.setIndex($(this).parent().index());
                }}>
                <span className="PostPreview-content">
                  {avatar(user)}
                  {username} {' '}
                  {content}
                </span>
              </button>
            );
          };

          const buildSuggestions = () => {
            const suggestions = [];

            // If the user has started to type a username, then suggest users
            // matching that username.
            if (typed) {
              app.store.all('users').forEach(user => {
                if (user.username().toLowerCase().substr(0, typed.length) !== typed) return;

                suggestions.push(
                  makeSuggestion(user, '@' + user.username(), '', 'MentionsDropdown-user')
                );
              });
            }

            // If the user is replying to a discussion, or if they are editing a
            // post, then we can suggest other posts in the discussion to mention.
            // We will add the 5 most recent comments in the discussion which
            // match any username characters that have been typed.
            const composerPost = composer.props.post;
            const discussion = (composerPost && composerPost.discussion()) || composer.props.discussion;
            if (discussion) {
              discussion.posts()
                .filter(post => post && post.contentType() === 'comment' && (!composerPost || post.number() < composerPost.number()))
                .sort((a, b) => b.time() - a.time())
                .filter(post => {
                  const user = post.user();
                  return user && user.username().toLowerCase().substr(0, typed.length) === typed;
                })
                .splice(0, 5)
                .forEach(post => {
                  const user = post.user();
                  suggestions.push(
                    makeSuggestion(user, '@' + user.username() + '#' + post.id(), [
                      app.translator.trans('flarum-mentions.forum.composer.reply_to_post_text', {number: post.number()}), ' — ',
                      truncate(post.contentPlain(), 200)
                    ], 'MentionsDropdown-post')
                  );
                });
            }

            if (suggestions.length) {
              dropdown.props.items = suggestions;
              m.render($container[0], dropdown.render());

              dropdown.show();
              const coordinates = getCaretCoordinates(this, mentionStart);
              const width = dropdown.$().outerWidth();
              const height = dropdown.$().outerHeight();
              const parent = dropdown.$().offsetParent();
              let left = coordinates.left;
              let top = coordinates.top + 15;
              if (top + height > parent.height()) {
                top = coordinates.top - height - 15;
              }
              if (left + width > parent.width()) {
                left = parent.width() - width;
              }
              dropdown.show(left, top);
            }
          };

          buildSuggestions();

          dropdown.setIndex(0);
          dropdown.$().scrollTop(0);
          dropdown.active = true;

          clearTimeout(searchTimeout);
          if (typed) {
            searchTimeout = setTimeout(function() {
              const typedLower = typed.toLowerCase();
              if (searched.indexOf(typedLower) === -1) {
                app.store.find('users', {q: typed, page: {limit: 5}}).then(() => {
                  if (dropdown.active) buildSuggestions();
                });
                searched.push(typedLower);
              }
            }, 250);
          }
        }
      });
  });
}
