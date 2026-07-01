// Minimal markdown -> styled HTML (tables, headings, lists, bold/italic/code).
export function esc(s) {
  return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}
function inline(s) {
  return esc(s)
    .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
    .replace(/\*([^*]+)\*/g, '<em>$1</em>')
    .replace(/`([^`]+)`/g, '<code>$1</code>');
}
export function mdToHtml(md) {
  const lines = md.replace(/\r\n/g, '\n').split('\n');
  const out = [];
  let i = 0;
  const flushList = (buf, tag) => {
    if (!buf.length) return;
    out.push(`<${tag}>` + buf.map((li) => `<li>${inline(li)}</li>`).join('') + `</${tag}>`);
    buf.length = 0;
  };
  while (i < lines.length) {
    const line = lines[i];
    if (line.includes('|') && i + 1 < lines.length && /^\s*\|?[\s:|-]+\|?\s*$/.test(lines[i + 1]) && lines[i + 1].includes('-')) {
      const cells = (l) => l.split('|').map((c) => c.trim()).filter((c, idx, a) => !(c === '' && (idx === 0 || idx === a.length - 1)));
      const header = cells(line);
      i += 2;
      const rows = [];
      while (i < lines.length && lines[i].includes('|')) { rows.push(cells(lines[i])); i++; }
      out.push('<table><thead><tr>' + header.map((h) => `<th>${inline(h)}</th>`).join('') + '</tr></thead><tbody>' +
        rows.map((r) => '<tr>' + r.map((c) => `<td>${inline(c)}</td>`).join('') + '</tr>').join('') + '</tbody></table>');
      continue;
    }
    let m;
    if ((m = line.match(/^(#{1,6})\s+(.*)$/))) {
      out.push(`<h${m[1].length}>${inline(m[2])}</h${m[1].length}>`); i++;
    } else if (/^\s*([-*])\s+/.test(line)) {
      const buf = [];
      while (i < lines.length && /^\s*([-*])\s+/.test(lines[i])) { buf.push(lines[i].replace(/^\s*[-*]\s+/, '')); i++; }
      flushList(buf, 'ul');
    } else if (/^\s*\d+\.\s+/.test(line)) {
      const buf = [];
      while (i < lines.length && /^\s*\d+\.\s+/.test(lines[i])) { buf.push(lines[i].replace(/^\s*\d+\.\s+/, '')); i++; }
      flushList(buf, 'ol');
    } else if (/^\s*---\s*$/.test(line)) {
      out.push('<hr/>'); i++;
    } else if (line.trim() === '') {
      i++;
    } else {
      const buf = [line]; i++;
      while (i < lines.length && lines[i].trim() !== '' && !/^(#{1,6}\s|\s*[-*]\s|\s*\d+\.\s|---\s*$)/.test(lines[i]) && !lines[i].includes('|')) {
        buf.push(lines[i]); i++;
      }
      out.push(`<p>${buf.map(inline).join('<br/>')}</p>`);
    }
  }
  return out.join('\n');
}
const STYLE = `
  @page { margin: 18mm 16mm; }
  * { box-sizing: border-box; }
  body { font-family: -apple-system, "Helvetica Neue", Arial, sans-serif; color: #1a1a1a; font-size: 12px; line-height: 1.5; padding: 8px; }
  h1 { font-size: 22px; margin: 0 0 4px; border-bottom: 2px solid #1a1a1a; padding-bottom: 6px; }
  h2 { font-size: 15px; margin: 18px 0 6px; color: #333; }
  h3 { font-size: 13px; margin: 12px 0 4px; color: #444; }
  table { width: 100%; border-collapse: collapse; margin: 8px 0 14px; font-size: 11px; }
  th, td { border: 1px solid #cfcfcf; padding: 5px 8px; text-align: left; }
  th { background: #f0f0f0; }
  code { background: #f2f2f2; padding: 1px 4px; border-radius: 3px; }
  hr { border: none; border-top: 1px solid #ddd; margin: 14px 0; }
  p { margin: 6px 0; }
`;
export function htmlDoc(body) {
  return `<!doctype html><html><head><meta charset="utf-8"><style>${STYLE}</style></head><body>${body}</body></html>`;
}
