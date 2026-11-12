// PostToolUse hook: after code that defines behavior is edited, remind Claude to keep docs/PRD.md in sync.
let raw = '';
process.stdin.on('data', (c) => (raw += c));
process.stdin.on('end', () => {
  let file = '';
  try {
    const input = JSON.parse(raw || '{}');
    file = (input.tool_input && input.tool_input.file_path) || (input.tool_response && input.tool_response.filePath) || '';
  } catch (e) {
    return;
  }
  const root = process.env.CLAUDE_PROJECT_DIR || process.cwd();
  const rel = require('path').relative(root, file).replace(/\\/g, '/');
  const watched = /^(app|routes|database\/migrations|database\/seeders|config|resources\/views)\//i;
  if (!watched.test(rel) || /docs\/PRD\.md$/i.test(rel)) return;
  process.stdout.write(JSON.stringify({
    hookSpecificOutput: {
      hookEventName: 'PostToolUse',
      additionalContext: `You changed ${rel}. Before finishing this task, update docs/PRD.md so it matches the new behavior (the affected module section, plus a line in the Changelog with today's date), and commit it together with the code change.`,
    },
  }));
});
