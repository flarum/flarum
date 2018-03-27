/*global getCaretCoordinates*/

import { extend } from 'flarum/extend';
import ComposerBody from 'flarum/components/ComposerBody';
import emojiMap from 'flarum/emoji/helpers/emojiMap';
import KeyboardNavigatable from 'flarum/utils/KeyboardNavigatable';

import AutocompleteDropdown from 'flarum/emoji/components/AutocompleteDropdown';

export default function addComposerAutocomplete() {

  const emojiKeys = Object.keys(emojiMap);

  extend(ComposerBody.prototype, 'config', function(original, isInitialized) {
    if (isInitialized) return;

    const composer = this;
    const $container = $('<div class="ComposerBody-emojiDropdownContainer"></div>');
    const dropdown = new AutocompleteDropdown({items: []});
    const $textarea = this.$('textarea').wrap('<div class="ComposerBody-emojiWrapper"></div>');
    let emojiStart;
    let typed;

    const applySuggestion = function(replacement) {
      const insert = replacement + ' ';

      const content = composer.content();
      composer.editor.setValue(content.substring(0, emojiStart - 1) + insert + content.substr($textarea[0].selectionStart));

      const index = emojiStart - 1 + insert.length;
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

        // Search backwards from the cursor for an ':' symbol. If we find
        // one and followed by a whitespace, we will want to show the
        // autocomplete dropdown!
        const value = this.value;
        emojiStart = 0;
        for (let i = cursor - 1; i >= 0; i--) {
          const character = value.substr(i, 1);
          // check what user typed, emoji names only contains alphanumeric,
          // underline, '+' and '-'
          if (!/[a-z0-9]|\+|\-|_|\:/.test(character)) break;
          // make sure ':' followed by a whitespace or newline
          if (character === ':' && (i == 0 || /\s/.test(value.substr(i - 1, 1)))) {
            emojiStart = i + 1;
            break;
          }
        }

        dropdown.hide();
        dropdown.active = false;

        if (emojiStart) {
          typed = value.substring(emojiStart, cursor).toLowerCase();

          const makeSuggestion = function(key) {
            const code = ':' + key + ':';
            const imageName = emojiMap[key];
            return (
              <button
                key={key}
                onclick={() => applySuggestion(code)}
                onmouseenter={function() {
                  dropdown.setIndex($(this).parent().index());
                }}>
                  <img alt={code} class="emoji" draggable="false" src={'//cdn.jsdelivr.net/emojione/assets/png/' + imageName + '.png'}/>
                  {key}
              </button>
            );
          };

          const buildSuggestions = () => {
            const suggestions = [];
            let similarEmoji = [];

            // Build a regular expression to do a fuzzy match of the given input string
            const fuzzyRegexp = function(str) {
              const reEscape = new RegExp('\\(([' + ('+.*?[]{}()^$|\\'.replace(/(.)/g, '\\$1')) + '])\\)', 'g');
              return new RegExp('(.*)' + (str.toLowerCase().replace(/(.)/g, '($1)(.*?)')).replace(reEscape, '(\\$1)') + '$', 'i');
            };
            const regTyped = fuzzyRegexp(typed);

            let maxSuggestions = 7;

            const findMatchingEmojis = matcher => {
              for (let i = 0; i < emojiKeys.length && maxSuggestions > 0; i++) {
                const curEmoji = emojiKeys[i];
                if (matcher(curEmoji) && similarEmoji.indexOf(curEmoji) === -1) {
                  --maxSuggestions;
                  similarEmoji.push(emojiKeys[i]);
                }
              }
            };

            // First, try to find all emojis starting with the given string
            findMatchingEmojis(emoji => emoji.indexOf(typed) === 0);

            // If there are still suggestions left, try for some fuzzy matches
            findMatchingEmojis(emoji => regTyped.test(emoji));

            similarEmoji = similarEmoji.sort((a, b) => {
              return a.length - b.length
            });

            for (let key of similarEmoji) {
              suggestions.push(makeSuggestion(key));
            }

            if (suggestions.length) {
              dropdown.props.items = suggestions;
              m.render($container[0], dropdown.render());

              dropdown.show();
              const coordinates = getCaretCoordinates(this, emojiStart);
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
        }
      });
  });
}
