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
      additionalContext: `You changed ${rel}. Before finishing this task, make a new PRD version: copy docs/PRD.md to docs/prd-history/PRD-v<current version>.md, update the affected module section of docs/PRD.md, bump "Versi PRD", add a Changelog row (date, version, summary), and commit it together with the code change (see CLAUDE.md).`,
    },
  }));
});
