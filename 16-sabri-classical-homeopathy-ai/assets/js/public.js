(() => {
  'use strict';

  const cfg = window.SCHA_APP || {};

  const rest = (path, options = {}) => {
    const headers = Object.assign({
      'Content-Type': 'application/json',
      'X-WP-Nonce': cfg.nonce || '',
      'X-SCHA-Guest-Token': cfg.guestToken || '',
    }, options.headers || {});
    return fetch(`${cfg.restRoot || ''}${path}`, Object.assign({
      credentials: 'same-origin', cache: 'no-store', referrerPolicy: 'no-referrer', headers,
    }, options)).then(async (response) => {
      const body = await response.json().catch(() => ({}));
      if (!response.ok) {
        const error = new Error(body.message || cfg.strings?.error || 'Request failed.');
        error.status = response.status; error.data = body.data || {}; throw error;
      }
      return body;
    });
  };

  const uuid = () => window.crypto?.randomUUID ? window.crypto.randomUUID() : `scha-${Date.now()}-${Math.random().toString(36).slice(2, 14)}`;
  const status = (text, isError = false) => document.querySelectorAll('[data-scha-status]').forEach((node) => { node.textContent = text || ''; node.classList.toggle('is-error', Boolean(isError)); });
  const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[char]));
  const linkifySafe = (text) => escapeHtml(text).replace(/(https?:\/\/[^\s<]+)/g, (url) => {
    try { const parsed = new URL(url); return ['http:', 'https:'].includes(parsed.protocol) ? `<a href="${escapeHtml(parsed.href)}" target="_blank" rel="noopener noreferrer">${escapeHtml(parsed.href)}</a>` : url; } catch (_) { return url; }
  }).replace(/\n/g, '<br>');

  const createSession = () => {
    const button = document.querySelector('[data-scha-create-session]');
    if (!button) return;
    button.addEventListener('click', async () => {
      button.disabled = true; status(cfg.strings?.working || 'Working…');
      const mode = document.querySelector('[data-scha-mode]')?.value || 'study';
      try { const body = await rest('sessions', { method: 'POST', body: JSON.stringify({ mode }) }); window.location.assign(body.url); }
      catch (error) { status(error.message, true); button.disabled = false; }
    });
  };

  const messageNode = (message) => {
    const article = document.createElement('article');
    article.className = `scha-message scha-message-${message.role || 'assistant'}`; article.dataset.messageId = message.id || '';
    const heading = document.createElement('h3'); heading.textContent = message.role === 'user' ? (cfg.strings?.you || 'You') : (cfg.strings?.assistant || 'Sabri AI'); article.appendChild(heading);
    const content = document.createElement('div'); content.className = 'scha-message-content'; content.innerHTML = linkifySafe(message.content || ''); article.appendChild(content);
    if (Array.isArray(message.citations) && message.citations.length) {
      const list = document.createElement('ol'); list.className = 'scha-citations'; list.setAttribute('aria-label', cfg.strings?.citations || 'Citations');
      message.citations.forEach((citation) => {
        const item = document.createElement('li'); const label = `${citation.marker}: ${citation.title} — ${citation.version}, ${citation.location}`;
        if (citation.url) { const link = document.createElement('a'); link.href = citation.url; link.target = '_blank'; link.rel = 'noopener noreferrer'; link.textContent = label; item.appendChild(link); } else item.textContent = label;
        list.appendChild(item);
      }); article.appendChild(list);
    }
    if (message.role === 'assistant' && message.id && cfg.isLoggedIn) {
      const controls = document.createElement('div'); controls.className = 'scha-feedback-controls';
      ['helpful', 'unhelpful', 'citation_issue', 'unsafe_answer'].forEach((category) => {
        const button = document.createElement('button'); button.type = 'button'; button.className = 'scha-button scha-button-small scha-button-quiet'; button.textContent = category.replace('_', ' ');
        button.addEventListener('click', async () => { button.disabled = true; try { await rest('feedback', { method: 'POST', body: JSON.stringify({ message_id: message.id, category }) }); controls.textContent = cfg.strings?.feedback || 'Feedback received.'; } catch (error) { button.disabled = false; status(error.message, true); } });
        controls.appendChild(button);
      }); article.appendChild(controls);
    }
    return article;
  };

  const initChat = async () => {
    const chat = document.querySelector('[data-scha-chat]'); if (!chat) return;
    const messagesNode = chat.querySelector('[data-scha-messages]'); const form = chat.querySelector('[data-scha-prompt-form]'); const sessionId = chat.dataset.sessionId;
    const prompt = form?.querySelector('textarea[name="prompt"]'); const submit = form?.querySelector('button[type="submit"]'); const deleteButton = chat.querySelector('[data-scha-delete-session]'); const exportLink = chat.querySelector('[data-scha-export]');
    const renderMessages = (messages) => { messagesNode.innerHTML = ''; if (!messages?.length) { const empty = document.createElement('p'); empty.className = 'scha-empty-inline'; empty.textContent = cfg.strings?.noMessages || 'No question has been asked in this session.'; messagesNode.appendChild(empty); } else messages.forEach((message) => messagesNode.appendChild(messageNode(message))); messagesNode.setAttribute('aria-busy', 'false'); messagesNode.scrollTop = messagesNode.scrollHeight; };
    try { const body = await rest(`sessions/${encodeURIComponent(sessionId)}`); renderMessages(body.messages || []); }
    catch (error) { messagesNode.setAttribute('aria-busy', 'false'); messagesNode.innerHTML = `<p class="scha-warning">${escapeHtml(error.message)}</p>`; if (form) form.hidden = true; return; }

    form?.addEventListener('submit', async (event) => {
      event.preventDefault(); const value = prompt.value.trim(); if (!value) { status(cfg.strings?.empty || 'Please enter a question.', true); prompt.focus(); return; }
      submit.disabled = true; prompt.disabled = true; status(cfg.strings?.working || 'Working…'); const optimistic = messageNode({ role: 'user', content: value, id: '' }); optimistic.classList.add('is-pending'); messagesNode.appendChild(optimistic); messagesNode.scrollTop = messagesNode.scrollHeight;
      try {
        const body = await rest(`sessions/${encodeURIComponent(sessionId)}/answer`, { method: 'POST', body: JSON.stringify({ prompt: value, idempotency_key: uuid() }) });
        optimistic.remove(); messagesNode.appendChild(messageNode({ role: 'user', content: value, id: '' })); messagesNode.appendChild(messageNode(body.message));
        if (body.bridge_url) { const bridge = document.createElement('p'); bridge.className = 'scha-disclosure'; const a = document.createElement('a'); a.className = 'scha-button scha-button-primary'; a.href = body.bridge_url; a.target = '_blank'; a.rel = 'noopener noreferrer'; a.textContent = 'Open the external Custom GPT'; bridge.appendChild(a); messagesNode.appendChild(bridge); }
        prompt.value = ''; status(body.disclosure || cfg.strings?.delivered || 'Answer delivered.'); messagesNode.scrollTop = messagesNode.scrollHeight;
      } catch (error) { optimistic.classList.remove('is-pending'); optimistic.classList.add('is-error'); status(error.message, true); }
      finally { submit.disabled = false; prompt.disabled = false; prompt.focus(); }
    });

    deleteButton?.addEventListener('click', async () => {
      if (!window.confirm(cfg.strings?.deleteConfirm || 'Delete this session?')) return;
      deleteButton.disabled = true; try { await rest(`sessions/${encodeURIComponent(sessionId)}`, { method: 'DELETE' }); status(cfg.strings?.deleted || 'Session deleted.'); window.location.assign(cfg.historyUrl || cfg.homeUrl); } catch (error) { deleteButton.disabled = false; status(error.message, true); }
    });

    exportLink?.addEventListener('click', async (event) => {
      event.preventDefault(); try { const body = await rest(`sessions/${encodeURIComponent(sessionId)}/export`); const blob = new Blob([JSON.stringify(body, null, 2)], { type: 'application/json' }); const url = URL.createObjectURL(blob); const anchor = document.createElement('a'); anchor.href = url; anchor.download = `scha-session-${sessionId}.json`; anchor.click(); setTimeout(() => URL.revokeObjectURL(url), 1000); } catch (error) { status(error.message, true); }
    });
  };

  const initHistory = async () => {
    const container = document.querySelector('[data-scha-history]'); if (!container) return;
    try {
      const body = await rest('sessions'); container.innerHTML = ''; const sessions = Array.isArray(body) ? body : (body.sessions || []);
      if (!sessions.length) container.innerHTML = `<div class="scha-empty-inline"><p>${escapeHtml(cfg.strings?.noSessions || 'No AI sessions yet.')}</p></div>`;
      else { const list = document.createElement('div'); list.className = 'scha-history-list'; sessions.forEach((session) => { const article = document.createElement('article'); article.className = 'scha-history-item'; const info = document.createElement('div'); const h = document.createElement('h2'); h.textContent = session.modeLabel || `Session ${session.id.slice(0, 8)}`; const p = document.createElement('p'); p.textContent = `${session.provider} · ${session.updatedAt} · retention ${session.retentionUntil}`; info.append(h, p); const a = document.createElement('a'); a.className = 'scha-button scha-button-primary'; a.href = session.url; a.textContent = cfg.strings?.open || 'Open'; article.append(info, a); list.appendChild(article); }); container.appendChild(list); }
      container.setAttribute('aria-busy', 'false');
    } catch (error) { container.setAttribute('aria-busy', 'false'); container.innerHTML = `<p class="scha-warning">${escapeHtml(error.message)}</p>`; }
  };

  const initBandwidth = () => {
    let storedPreference = false;
    try { storedPreference = window.localStorage?.getItem('scha-low-bandwidth') === '1'; } catch (_) { storedPreference = false; }
    if (cfg.lowBandwidth || storedPreference) document.documentElement.classList.add('scha-low-bandwidth');
  };

  document.addEventListener('DOMContentLoaded', () => { initBandwidth(); createSession(); initChat(); initHistory(); });
})();
