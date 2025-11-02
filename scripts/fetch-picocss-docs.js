#!/usr/bin/env node
/*
 Fetch PicoCSS docs pages and convert to Markdown under .codex/picocss-docs.
 Requires devDependencies: turndown, jsdom, node-fetch.
*/
const fs = require('fs');
const path = require('path');
const TurndownService = require('turndown');
const { JSDOM } = require('jsdom');
let fetchFn;
try {
  // Node-fetch v3 is ESM-only; use dynamic import wrapper
  fetchFn = (...args) => import('node-fetch').then(({default: f}) => f(...args));
} catch (e) {
  fetchFn = global.fetch;
}

const outDir = path.resolve(__dirname, '../..', '.codex/picocss-docs');
fs.mkdirSync(outDir, { recursive: true });

const base = 'https://picocss.com';
const pages = [
  ['getting-started', '/docs/getting-started'],
  ['quick-start', '/docs/quick-start'],
  ['version-picker', '/docs/version-picker'],
  ['color-schemes', '/docs/color-schemes'],
  ['classless', '/docs/classless'],
  ['conditional', '/docs/conditional-styling'],
  ['rtl', '/docs/rtl'],
  ['customization', '/docs/customization'],
  ['css-variables', '/docs/css-variables'],
  ['sass', '/docs/sass'],
  ['colors', '/docs/colors'],
  ['layout', '/docs/layout'],
  ['container', '/docs/container'],
  ['landmarks', '/docs/landmarks'],
  ['grid', '/docs/grid'],
  ['overflow-auto', '/docs/overflow-auto'],
  // Content
  ['typography', '/docs/typography'],
  ['link', '/docs/link'],
  ['button', '/docs/button'],
  ['table', '/docs/table'],
  // Forms
  ['forms-overview', '/docs/forms'],
  ['input', '/docs/input'],
  ['textarea', '/docs/textarea'],
  ['select', '/docs/select'],
  ['checkboxes', '/docs/checkbox'],
  ['radios', '/docs/radio'],
  ['switch', '/docs/switch'],
  ['range', '/docs/range'],
  // Components
  ['accordion', '/docs/accordion'],
  ['card', '/docs/card'],
  ['dropdown', '/docs/dropdown'],
  ['group', '/docs/group'],
  ['loading', '/docs/loading'],
  ['modal', '/docs/modal'],
  ['nav', '/docs/nav'],
  ['progress', '/docs/progress'],
  ['tooltip', '/docs/tooltip']
];

const turndown = new TurndownService({ headingStyle: 'atx', codeBlockStyle: 'fenced' });
turndown.addRule('removeNav', {
  filter: (node) => node.nodeName === 'NAV'
});

async function fetchPage(slug, urlPath) {
  const url = base + urlPath;
  const res = await fetchFn(url);
  if (!res.ok) {
    console.error('Skip', slug, res.status);
    return;
  }
  const html = await res.text();
  const dom = new JSDOM(html);
  const d = dom.window.document;
  let main = d.querySelector('main') || d.querySelector('article') || d.body;
  // Remove header/footer/nav
  ['header','footer','nav','.menu','.sidebar'].forEach(sel=>{
    main.querySelectorAll(sel).forEach(n=>n.remove());
  });
  const title = (d.querySelector('h1') && d.querySelector('h1').textContent.trim()) || slug;
  const md = `# ${title}\n\nSource: ${url}\n\n` + turndown.turndown(main.innerHTML);
  const file = path.join(outDir, slug + '.md');
  fs.writeFileSync(file, md);
  console.log('Wrote', file);
}

(async () => {
  for (const [slug, p] of pages) {
    try { await fetchPage(slug, p); } catch (e) { console.error('Error', slug, e.message); }
  }
})();
