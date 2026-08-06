(() => {
  'use strict';
  const cfg = window.SCHA_APP || {};
  const rest = (path, options = {}) => fetch(`${cfg.restRoot || ''}${path}`, Object.assign({ credentials: 'same-origin', cache: 'no-store', headers: Object.assign({ 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce || '' }, options.headers || {}) }, options)).then(async (response) => {
    const body = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(body.message || cfg.strings?.error || 'Request failed.');
    return body;
  });
  const uuid = () => window.crypto?.randomUUID ? window.crypto.randomUUID() : `scha-${Date.now()}-${Math.random().toString(36).slice(2, 14)}`;
  const status = (text, error = false) => document.querySelectorAll('[data-scha-status]').forEach((node) => { node.textContent = text || ''; node.classList.toggle('is-error', error); });
  const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[char]));
  const safeText = (text) => escapeHtml(text).replace(/\n/g, '<br>');
  const messageNode = (message) => {
    const article = document.createElement('article');
    article.className = `scha-message scha-message-${message.role || 'assistant'}`;
    article.dataset.messageId = message.id || '';
    article.innerHTML = `<h3>${message.role === 'user' ? 'You' : 'Sabri AI'}</h3><div>${safeText(message.content || '')}</div>`;
    if (Array.isArray(message.citations) && message.citations.length) {
      const list = document.createElement('ol'); list.className = 'scha-citations';
      message.citations.forEach((citation) => { const item = document.createElement('li'); item.textContent = `${citation.marker}: ${citation.title} — ${citation.version}, ${citation.location}`; list.appendChild(item); });
      article.appendChild(list);
    }
    if (message.role === 'assistant' && message.id) {
      const controls = document.createElement('div'); controls.className = 'scha-feedback-controls';
      ['helpful', 'unhelpful', 'citation_issue', 'unsafe_answer'].forEach((category) => { const button = document.createElement('button'); button.type = 'button'; button.className = 'scha-button scha-button-small scha-button-quiet'; button.textContent = category.replace('_', ' '); button.addEventListener('click', async () => { button.disabled = true; try { await rest('feedback', { method: 'POST', body: JSON.stringify({ message_id: message.id, category }) }); controls.textContent = 'Feedback received.'; } catch (error) { button.disabled = false; status(error.message, true); } }); controls.appendChild(button); });
      article.appendChild(controls);
    }
    return article;
  };
  const createButton = document.querySelector('[data-scha-create-session]');
  createButton?.addEventListener('click', async () => { createButton.disabled = true; status(cfg.strings?.working || 'Working…'); try { const body = await rest('sessions', { method: 'POST', body: '{}' }); window.location.assign(body.url); } catch (error) { status(error.message, true); createButton.disabled = false; } });
  const chat = document.querySelector('[data-scha-chat]');
  if (chat) {
    const messages = chat.querySelector('[data-scha-messages]'); const form = chat.querySelector('[data-scha-prompt-form]'); const prompt = form.querySelector('textarea'); const submit = form.querySelector('button[type="submit"]'); const id = chat.dataset.sessionId;
    const render = (items) => { messages.innerHTML = ''; (items || []).forEach((item) => messages.appendChild(messageNode(item))); if (!items?.length) messages.innerHTML = '<p class="scha-empty-inline">No question has been asked.</p>'; messages.setAttribute('aria-busy', 'false'); };
    rest(`sessions/${encodeURIComponent(id)}`).then((body) => render(body.messages)).catch((error) => { messages.innerHTML = `<p class="scha-warning">${escapeHtml(error.message)}</p>`; form.hidden = true; });
    form.addEventListener('submit', async (event) => { event.preventDefault(); const value = prompt.value.trim(); if (!value) return status(cfg.strings?.empty || 'Please enter a question.', true); submit.disabled = true; prompt.disabled = true; status(cfg.strings?.working || 'Working…'); try { const body = await rest(`sessions/${encodeURIComponent(id)}/answer`, { method: 'POST', body: JSON.stringify({ prompt: value, idempotency_key: uuid() }) }); messages.appendChild(messageNode({ role: 'user', content: value })); messages.appendChild(messageNode(body.message)); if (body.bridge_url) { const p = document.createElement('p'); p.className = 'scha-disclosure'; const a = document.createElement('a'); a.className = 'scha-button scha-button-primary'; a.href = body.bridge_url; a.target = '_blank'; a.rel = 'noopener noreferrer'; a.textContent = 'Open external Custom GPT'; p.appendChild(a); messages.appendChild(p); } prompt.value = ''; status(body.disclosure || 'Answer delivered.'); } catch (error) { status(error.message, true); } finally { submit.disabled = false; prompt.disabled = false; prompt.focus(); } });
    chat.querySelector('[data-scha-delete-session]')?.addEventListener('click', async (event) => { if (!window.confirm('Delete this session?')) return; event.currentTarget.disabled = true; try { await rest(`sessions/${encodeURIComponent(id)}`, { method: 'DELETE' }); window.location.assign(cfg.historyUrl || cfg.homeUrl); } catch (error) { event.currentTarget.disabled = false; status(error.message, true); } });
    chat.querySelector('[data-scha-export]')?.addEventListener('click', async (event) => { event.preventDefault(); try { const body = await rest(`sessions/${encodeURIComponent(id)}/export`); const url = URL.createObjectURL(new Blob([JSON.stringify(body, null, 2)], { type: 'application/json' })); const a = document.createElement('a'); a.href = url; a.download = `scha-session-${id}.json`; a.click(); setTimeout(() => URL.revokeObjectURL(url), 1000); } catch (error) { status(error.message, true); } });
  }
  const history = document.querySelector('[data-scha-history]');
  if (history) rest('sessions').then((body) => { const sessions = Array.isArray(body) ? body : (body.sessions || []); history.innerHTML = sessions.length ? '<div class="scha-history-list"></div>' : '<p>No AI sessions yet.</p>'; const list = history.querySelector('.scha-history-list'); sessions.forEach((session) => { const item = document.createElement('article'); item.className = 'scha-history-item'; const meta = document.createElement('div'); meta.innerHTML = `<h2>Session ${escapeHtml(session.id.slice(0, 8))}</h2><p>${escapeHtml(session.provider)} · ${escapeHtml(session.updated_at)}</p>`; const link = document.createElement('a'); link.className = 'scha-button scha-button-primary'; link.href = session.url; link.textContent = 'Open'; item.append(meta, link); list.appendChild(item); }); history.setAttribute('aria-busy', 'false'); }).catch((error) => { history.innerHTML = `<p class="scha-warning">${escapeHtml(error.message)}</p>`; });
})();
