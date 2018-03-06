import DiscussionControls from 'flarum/utils/DiscussionControls';

function insertMention(post, component, quote) {
  const user = post.user();
  const mention = '@' + (user ? user.username() : post.number()) + '#' + post.id() + ' ';

  // If the composer is empty, then assume we're starting a new reply.
  // In which case we don't want the user to have to confirm if they
  // close the composer straight away.
  if (!component.content()) {
    component.props.originalContent = mention;
  }

  const cursorPosition = component.editor.getSelectionRange()[0];
  const preceding = component.editor.value().slice(0, cursorPosition);
  const precedingNewlines = preceding.length == 0 ? 0 : 3 - preceding.match(/(\n{0,2})$/)[0].length;

  component.editor.insertAtCursor(
    Array(precedingNewlines).join('\n') + // Insert up to two newlines, depending on preceding whitespace
    (quote
      ? '> ' + mention + quote.trim().replace(/\n/g, '\n> ') + '\n\n'
      : mention)
  );
}

export default function reply(post, quote) {
  const component = app.composer.component;
  if (component && component.props.post && component.props.post.discussion() === post.discussion()) {
    insertMention(post, component, quote);
  } else {
    DiscussionControls.replyAction.call(post.discussion())
      .then(newComponent => insertMention(post, newComponent, quote));
  }
}
