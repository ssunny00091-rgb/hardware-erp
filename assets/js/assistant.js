const chatLog = document.getElementById("chat-log");
const chatInput = document.getElementById("chat-input");
const chatFile = document.getElementById("chat-file");
const chatFiles = document.getElementById("chat-files");
const btnAddMore = document.getElementById("btn-add-more");
const btnSend = document.getElementById("btn-send");
const btnMic = document.getElementById("btn-mic");
const btnSaveKey = document.getElementById("btn-save-key");
const keyStatus = document.getElementById("assistant-key-status");

const history = [];
let pendingFiles = [];
let listening = false;
let recognition = null;
let busy = false;
let voiceSendTimer = null;
let mediaRecorder = null;
let mediaStream = null;
let recordedChunks = [];
let audioCtx = null;
const VOICE_SILENCE_MS = 2800;
const btnSound = document.getElementById("btn-sound");
const micStatus = document.getElementById("mic-status");
const httpsHint = document.getElementById("https-mic-hint");
const chatVoiceFile = document.getElementById("chat-voice-file");

function currentVoiceLang() {
  const picked = document.querySelector('input[name="voice-lang"]:checked');
  return (picked && picked.value) || localStorage.getItem("assistantVoiceLang") || "hi-IN";
}

function applyVoiceLang(lang) {
  const value = lang === "en-IN" ? "en-IN" : "hi-IN";
  localStorage.setItem("assistantVoiceLang", value);
  document.querySelectorAll('input[name="voice-lang"]').forEach((el) => {
    el.checked = el.value === value;
  });
}

function addBubble(role, text, extra, tables) {
  const wrap = document.createElement("div");
  wrap.className = "chat-bubble " + (role === "user" ? "chat-user" : "chat-bot");
  const roleEl = document.createElement("div");
  roleEl.className = "chat-role";
  roleEl.textContent = role === "user" ? "Aap" : "Assistant";
  wrap.appendChild(roleEl);
  const body = document.createElement("div");
  body.className = "chat-text";
  if (role === "assistant") {
    renderRichReply(body, text, tables || []);
  } else {
    body.textContent = text;
  }
  wrap.appendChild(body);
  if (extra) {
    const meta = document.createElement("div");
    meta.className = "chat-meta";
    meta.textContent = extra;
    wrap.appendChild(meta);
  }
  chatLog.appendChild(wrap);
  chatLog.scrollTop = chatLog.scrollHeight;
}

function isMdTableSep(line) {
  return /^\s*\|?[\s:|-]+\|[\s:|-]+\|?\s*$/.test(line) && line.indexOf("-") !== -1;
}

function splitMdRow(line) {
  let s = String(line).trim();
  if (s.startsWith("|")) s = s.slice(1);
  if (s.endsWith("|")) s = s.slice(0, -1);
  return s.split("|").map((c) => c.trim());
}

function appendHtmlTable(parent, title, headers, rows) {
  if (!headers || !headers.length || !rows || !rows.length) return;
  const box = document.createElement("div");
  box.className = "chat-table-wrap";
  if (title) {
    const h = document.createElement("div");
    h.className = "chat-table-title";
    h.textContent = title;
    box.appendChild(h);
  }
  const table = document.createElement("table");
  table.className = "chat-table";
  const thead = document.createElement("thead");
  const hr = document.createElement("tr");
  headers.forEach((h) => {
    const th = document.createElement("th");
    th.textContent = h;
    hr.appendChild(th);
  });
  thead.appendChild(hr);
  table.appendChild(thead);
  const tb = document.createElement("tbody");
  rows.forEach((row) => {
    const tr = document.createElement("tr");
    headers.forEach((_, i) => {
      const td = document.createElement("td");
      td.textContent = row[i] == null ? "" : String(row[i]);
      tr.appendChild(td);
    });
    tb.appendChild(tr);
  });
  table.appendChild(tb);
  box.appendChild(table);
  parent.appendChild(box);
}

function appendActionBar(parent, actions) {
  if (!actions || !actions.length) return;
  const bar = document.createElement("div");
  bar.className = "chat-actions";
  actions.forEach((a) => {
    if (!a || !a.href) return;
    const link = document.createElement("a");
    link.className = "chat-action-btn chat-action-" + (a.kind || "view");
    link.href = a.href;
    link.textContent = a.label || "Open";
    if (a.target) link.target = a.target;
    bar.appendChild(link);
  });
  parent.appendChild(bar);
}

function renderRichReply(el, text, tables) {
  el.innerHTML = "";
  const apiTables = tables || [];
  const lines = String(text || "").replace(/\r\n/g, "\n").split("\n");
  let i = 0;
  const para = [];
  const flushPara = () => {
    if (!para.length) return;
    const p = document.createElement("div");
    p.className = "chat-para";
    p.textContent = para.join("\n");
    el.appendChild(p);
    para.length = 0;
  };
  while (i < lines.length) {
    if (apiTables.length === 0 && i + 1 < lines.length && lines[i].includes("|") && isMdTableSep(lines[i + 1])) {
      flushPara();
      const headers = splitMdRow(lines[i]);
      i += 2;
      const rows = [];
      while (i < lines.length && lines[i].includes("|") && !isMdTableSep(lines[i])) {
        rows.push(splitMdRow(lines[i]));
        i++;
      }
      appendHtmlTable(el, "", headers, rows);
      continue;
    }
    para.push(lines[i]);
    i++;
  }
  flushPara();
  apiTables.forEach((t) => {
    appendHtmlTable(el, t.title || "", t.headers || [], t.rows || []);
    appendActionBar(el, t.actions || []);
  });
}

function actionSummary(actions) {
  if (!actions || !actions.length) return "";
  return actions.map((a) => {
    const r = a.result || {};
    if (r.error) return a.tool + ": " + r.error;
    if (a.tool === "import_supplier_bill") {
      return "Supplier/bill save · #" + (r.id || r.party_id || "") + " · ₹" + formatMoney(r.total || 0);
    }
    if (a.tool === "create_sale") return "Sale " + (r.invoice_no || "") + " · ₹" + formatMoney(r.total || 0);
    if (a.tool === "get_profit_report") {
      const s = r.summary || {};
      return "Profit ₹" + formatMoney(s.profit || 0) + " · Sale ₹" + formatMoney(s.sales || 0);
    }
    if (a.tool === "get_invoice_detail") {
      return (r.kind === "purchase" ? "Purchase " : "Invoice ") + (r.invoice_no || r.id || "") + " · ₹" + formatMoney(r.total || 0);
    }
    if (a.tool === "set_due_reminder") {
      if (r.need_pick) return "Reminder: naam confirm karo";
      return "Reminder · " + (r.name || "") + " · " + (r.date || "");
    }
    if (a.tool === "list_reminders") return "Reminders: " + (r.count || 0);
    if (a.tool === "cancel_reminder") return "Reminder cancel #" + (r.id || "");
    if (a.tool === "add_or_update_party") return (r.type || "party") + " · " + (r.name || "");
    return a.tool + (a.ok ? " ✓" : "");
  }).join(" · ");
}

async function compressImage(file) {
  if (!file.type.startsWith("image/") || file.type === "image/gif") {
    return file;
  }
  const url = URL.createObjectURL(file);
  try {
    const img = await new Promise((resolve, reject) => {
      const el = new Image();
      el.onload = () => resolve(el);
      el.onerror = reject;
      el.src = url;
    });
    const max = 1600;
    let w = img.width;
    let h = img.height;
    if (w > max || h > max) {
      const scale = max / Math.max(w, h);
      w = Math.round(w * scale);
      h = Math.round(h * scale);
    }
    const canvas = document.createElement("canvas");
    canvas.width = w;
    canvas.height = h;
    canvas.getContext("2d").drawImage(img, 0, 0, w, h);
    const blob = await new Promise((resolve) => canvas.toBlob(resolve, "image/jpeg", 0.82));
    if (!blob) return file;
    return new File([blob], (file.name || "bill").replace(/\.[^.]+$/, "") + ".jpg", { type: "image/jpeg" });
  } catch (e) {
    return file;
  } finally {
    URL.revokeObjectURL(url);
  }
}

const MAX_BILL_PAGES = 12;

function showFiles() {
  if (!pendingFiles.length) {
    chatFiles.classList.add("hidden");
    chatFiles.innerHTML = "";
    if (btnAddMore) btnAddMore.classList.add("hidden");
    return;
  }
  chatFiles.classList.remove("hidden");
  chatFiles.innerHTML =
    '<div class="mb-1 text-sm text-emerald-200">' + pendingFiles.length + ' page' + (pendingFiles.length > 1 ? "s" : "") + ' attach — ek hi bill</div>' +
    pendingFiles.map((f, i) =>
      '<span class="photo-chip">Page ' + (i + 1) + ': ' + escapeHtml(f.name) +
      ' <button type="button" data-remove-photo="' + i + '" title="Remove">✕</button></span>'
    ).join("");
  if (btnAddMore) btnAddMore.classList.remove("hidden");
}

chatFiles.addEventListener("click", (e) => {
  const btn = e.target.closest("[data-remove-photo]");
  if (!btn) return;
  pendingFiles.splice(Number(btn.dataset.removePhoto), 1);
  showFiles();
});

if (btnAddMore) {
  btnAddMore.addEventListener("click", () => chatFile.click());
}

chatFile.addEventListener("change", async () => {
  const list = Array.from(chatFile.files || []);
  for (const file of list) {
    if (pendingFiles.length >= MAX_BILL_PAGES) {
      alert("Ek bill mein maximum " + MAX_BILL_PAGES + " pages.");
      break;
    }
    pendingFiles.push(await compressImage(file));
  }
  chatFile.value = "";
  showFiles();
});

async function sendChat(text) {
  const message = (text || chatInput.value || "").trim();
  if (busy) return;
  if (!message && !pendingFiles.length) return;
  busy = true;
  btnSend.disabled = true;
  const filesNow = pendingFiles.slice();
  let outgoing = message;
  const filesAreAudio = filesNow.length > 0 && filesNow.every((f) => {
    const t = (f.type || "").toLowerCase();
    const n = (f.name || "").toLowerCase();
    return t.startsWith("audio/") || t === "video/webm" || /\.(webm|m4a|mp3|wav|ogg|aac|3gp)$/.test(n);
  });
  if (!outgoing && filesNow.length) {
    outgoing = filesAreAudio
      ? "🎤 Voice note"
      : (filesNow.length > 1
        ? ("Yeh ek hi bill ki " + filesNow.length + " pages hain. Saari pages mila ke ek bill save karo.")
        : "Is photo se supplier bill save karo.");
  } else if (outgoing && filesNow.length > 1 && !filesAreAudio) {
    outgoing += " (Iske saath " + filesNow.length + " pages/photos hain — ek hi bill.)";
  }
  addBubble("user", outgoing || ("📎 " + filesNow.map((f) => f.name).join(", ")));
  chatInput.value = "";
  pendingFiles = [];
  showFiles();
  chatFile.value = "";
  showTyping();

  const form = new FormData();
  form.append("payload", JSON.stringify({
    message: filesAreAudio && !message ? "" : (outgoing || message),
    history,
  }));
  filesNow.forEach((file, i) => form.append("files[]", file, "page-" + (i + 1) + "-" + file.name));

  try {
    const response = await fetch(appUrl("/api/assistant.php"), {
      method: "POST",
      headers: { "X-Requested-With": "fetch", Accept: "application/json" },
      body: form,
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(data.error || "Assistant fail");
    const reply = data.reply || "Ho gaya.";
    const extra = actionSummary(data.actions || []);
    hideTyping();
    addBubble("assistant", reply, extra, data.tables || []);
    speakText(reply);
    history.push({ role: "user", content: outgoing || (filesAreAudio ? "Voice note" : "Photo/PDF bheji hai") });
    history.push({ role: "assistant", content: reply });
  } catch (err) {
    addBubble("assistant", "Error: " + err.message);
  } finally {
    hideTyping();
    busy = false;
    btnSend.disabled = false;
  }
}

btnSend.addEventListener("click", () => sendChat());
chatInput.addEventListener("keydown", (e) => {
  if (e.key === "Enter" && !e.shiftKey) {
    e.preventDefault();
    sendChat();
  }
});

let typingBubble = null;

function showTyping() {
  if (typingBubble) return;
  typingBubble = document.createElement("div");
  typingBubble.className = "chat-bubble chat-bot chat-typing";
  typingBubble.innerHTML = "<span></span><span></span><span></span>";
  chatLog.appendChild(typingBubble);
  chatLog.scrollTop = chatLog.scrollHeight;
}

function hideTyping() {
  if (typingBubble) {
    typingBubble.remove();
    typingBubble = null;
  }
}

document.querySelectorAll("#chat-chips [data-chip]").forEach((btn) => {
  btn.addEventListener("click", () => {
    const msg = (btn.dataset.chip || "").trim();
    if (!msg || busy) return;
    sendChat(msg);
  });
});

const btnOrMenu = document.getElementById("btn-or-menu");
const orPanel = document.getElementById("or-panel");
const btnOrClose = document.getElementById("btn-or-close");

function setOrMenuOpen(open) {
  if (!orPanel) return;
  orPanel.classList.toggle("hidden", !open);
  if (open) {
    orPanel.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }
}

if (btnOrMenu && orPanel) {
  btnOrMenu.addEventListener("click", () => {
    setOrMenuOpen(orPanel.classList.contains("hidden"));
  });
}
if (btnOrClose) {
  btnOrClose.addEventListener("click", () => setOrMenuOpen(false));
}

function micSupported() {
  return window.SpeechRecognition || window.webkitSpeechRecognition;
}

function isMobileShop() {
  return /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent || "")
    || ((navigator.maxTouchPoints || 0) > 1 && window.innerWidth < 920);
}

function canUseLiveMic() {
  const host = location.hostname;
  return window.isSecureContext || host === "localhost" || host === "127.0.0.1";
}

function setMicStatus(text, danger) {
  if (!micStatus) return;
  micStatus.textContent = text || "";
  micStatus.className = "mb-2 text-sm " + (danger ? "text-rose-300" : "text-amber-200");
}

function soundOn() {
  return localStorage.getItem("assistantSoundOn") === "1";
}

function renderSoundButton() {
  if (!btnSound) return;
  if (soundOn()) {
    btnSound.textContent = "🔊 Sound on";
    btnSound.classList.add("sound-on");
  } else {
    btnSound.textContent = "🔇 Sound band";
    btnSound.classList.remove("sound-on");
  }
}

function unlockAudio() {
  try {
    const Ctx = window.AudioContext || window.webkitAudioContext;
    if (Ctx) {
      if (!audioCtx) audioCtx = new Ctx();
      audioCtx.resume();
    }
  } catch (e) {
    // ignore
  }
  try {
    window.speechSynthesis && window.speechSynthesis.resume();
  } catch (e2) {
    // ignore
  }
}

function playBeep() {
  if (!soundOn()) return;
  try {
    unlockAudio();
    if (!audioCtx) return;
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.frequency.value = 880;
    gain.gain.setValueAtTime(0.0001, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.08, audioCtx.currentTime + 0.02);
    gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.18);
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    osc.start();
    osc.stop(audioCtx.currentTime + 0.2);
  } catch (e) {
    // ignore
  }
}

function pickVoice(lang) {
  if (!window.speechSynthesis) return null;
  const voices = window.speechSynthesis.getVoices() || [];
  const want = lang === "en-IN" ? ["en-IN", "en-GB", "hi-IN", "en"] : ["hi-IN", "hi", "en-IN", "en"];
  for (let i = 0; i < want.length; i++) {
    const hit = voices.find((v) => (v.lang || "").toLowerCase().startsWith(want[i].toLowerCase()));
    if (hit) return hit;
  }
  return voices[0] || null;
}

function speakText(text) {
  if (!soundOn() || !window.speechSynthesis) return;
  const plain = String(text || "")
    .replace(/[#*_`>|-]/g, " ")
    .replace(/\s+/g, " ")
    .trim()
    .slice(0, 450);
  if (!plain) return;
  unlockAudio();
  window.speechSynthesis.cancel();
  const u = new SpeechSynthesisUtterance(plain);
  u.lang = currentVoiceLang();
  u.rate = 1.04;
  const voice = pickVoice(u.lang);
  if (voice) u.voice = voice;
  window.speechSynthesis.speak(u);
}

function micIdleLabel() {
  btnMic.textContent = "🎤 Bolke";
  btnMic.classList.remove("ring-2", "ring-white", "mic-live");
}

function stopMediaStream() {
  if (mediaStream) {
    mediaStream.getTracks().forEach((t) => {
      try { t.stop(); } catch (e) { /* ignore */ }
    });
    mediaStream = null;
  }
}

function stopMic() {
  if (voiceSendTimer) {
    clearTimeout(voiceSendTimer);
    voiceSendTimer = null;
  }
  listening = false;
  if (recognition) {
    try {
      recognition.onend = null;
      recognition.onerror = null;
      recognition.stop();
    } catch (e) {
      // already stopped
    }
    recognition = null;
  }
  if (mediaRecorder && mediaRecorder.state === "recording") {
    try { mediaRecorder.stop(); } catch (e2) { /* ignore */ }
  }
  mediaRecorder = null;
  stopMediaStream();
  micIdleLabel();
}

function queueVoiceSend() {
  if (voiceSendTimer) clearTimeout(voiceSendTimer);
  voiceSendTimer = setTimeout(() => {
    voiceSendTimer = null;
    const text = (chatInput.value || "").trim();
    if (!text || !listening) return;
    stopMic();
    setMicStatus("Bhej raha hoon…");
    sendChat(text);
  }, VOICE_SILENCE_MS);
}

function recorderMime() {
  if (!window.MediaRecorder) return "";
  const types = [
    "audio/webm;codecs=opus",
    "audio/webm",
    "audio/mp4",
    "audio/ogg;codecs=opus",
    "audio/ogg",
  ];
  return types.find((t) => MediaRecorder.isTypeSupported(t)) || "";
}

function extForMime(mime) {
  if (mime.includes("mp4")) return "m4a";
  if (mime.includes("ogg")) return "ogg";
  if (mime.includes("mpeg") || mime.includes("mp3")) return "mp3";
  if (mime.includes("wav")) return "wav";
  return "webm";
}

async function askMicPermission() {
  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    throw new Error("Is browser mein mic API nahi hai");
  }
  const stream = await navigator.mediaDevices.getUserMedia({
    audio: { echoCancellation: true, noiseSuppression: true },
  });
  return stream;
}

async function startRecorder() {
  recordedChunks = [];
  const mime = recorderMime();
  mediaStream = await askMicPermission();
  mediaRecorder = mime
    ? new MediaRecorder(mediaStream, { mimeType: mime })
    : new MediaRecorder(mediaStream);
  const usedMime = mediaRecorder.mimeType || mime || "audio/webm";
  mediaRecorder.ondataavailable = (ev) => {
    if (ev.data && ev.data.size) recordedChunks.push(ev.data);
  };
  mediaRecorder.onerror = () => {
    setMicStatus("Recording error. Voice note try karo.", true);
    stopMic();
  };
  mediaRecorder.onstop = async () => {
    const chunks = recordedChunks.slice();
    recordedChunks = [];
    stopMediaStream();
    micIdleLabel();
    if (!chunks.length) {
      setMicStatus("Kuch record nahi hua. Phir se Bolke dabao.", true);
      return;
    }
    const blob = new Blob(chunks, { type: usedMime.split(";")[0] || "audio/webm" });
    if (blob.size < 800) {
      setMicStatus("Bahut chhoti recording. Thoda lamba bolo.", true);
      return;
    }
    const file = new File([blob], "voice." + extForMime(usedMime), { type: blob.type || "audio/webm" });
    setMicStatus("Voice bhej raha hoon…");
    pendingFiles.push(file);
    showFiles();
    await sendChat((chatInput.value || "").trim());
    setMicStatus("");
  };
  mediaRecorder.start(250);
  listening = true;
  btnMic.textContent = "⏹ Stop";
  btnMic.classList.add("ring-2", "ring-white", "mic-live");
  playBeep();
  setMicStatus("Sun raha hoon — bolo, khatam hone par Stop dabao.");
}

function startWebSpeech() {
  const Ctor = micSupported();
  if (!Ctor) {
    throw new Error("Live speech nahi");
  }
  recognition = new Ctor();
  recognition.lang = currentVoiceLang();
  recognition.interimResults = true;
  recognition.continuous = !isMobileShop();
  recognition.maxAlternatives = 1;
  listening = true;
  btnMic.textContent = "⏹ Stop";
  btnMic.classList.add("ring-2", "ring-white", "mic-live");
  playBeep();
  setMicStatus("Sun raha hoon — bolo…");
  recognition.onresult = (ev) => {
    let said = "";
    for (let i = 0; i < ev.results.length; i++) {
      said += ev.results[i][0].transcript;
    }
    chatInput.value = said.trim();
    setMicStatus(said.trim() ? ("Suna: " + said.trim()) : "Sun raha hoon…");
    if (ev.results[ev.results.length - 1].isFinal) {
      queueVoiceSend();
    }
  };
  recognition.onerror = (ev) => {
    const err = (ev && ev.error) || "";
    if (err === "no-speech" || err === "aborted") {
      return;
    }
    if (err === "not-allowed" || err === "service-not-allowed") {
      setMicStatus("Mic permission band hai. Chrome settings se mic allow karo, ya Voice note use karo.", true);
      stopMic();
      return;
    }
    if (err === "audio-capture" || err === "network") {
      setMicStatus("Live mic fail (" + err + "). Ab recording mode.", true);
      stopMic();
      startRecorder().catch(() => {
        if (chatVoiceFile) chatVoiceFile.click();
      });
      return;
    }
    setMicStatus("Mic error: " + err, true);
  };
  recognition.onend = () => {
    if (!listening || !recognition) return;
    const text = (chatInput.value || "").trim();
    if (isMobileShop()) {
      if (text) {
        queueVoiceSend();
        return;
      }
      try {
        setTimeout(() => {
          if (listening && recognition) recognition.start();
        }, 280);
      } catch (e) {
        queueVoiceSend();
      }
      return;
    }
    try {
      recognition.start();
    } catch (e2) {
      queueVoiceSend();
    }
  };
  recognition.start();
}

function showHttpsHint() {
  if (!httpsHint) return;
  if (canUseLiveMic()) {
    httpsHint.classList.add("hidden");
    return;
  }
  httpsHint.classList.remove("hidden");
  httpsHint.textContent = "Phone par live mic ke liye site HTTPS (lock) chahiye. Abhi http hai, isliye Bolke block ho sakta hai. Voice note button use karo — phone ka recorder chalega. Ya PC Chrome se localhost kholo.";
}

async function toggleMic() {
  if (listening) {
    if (mediaRecorder && mediaRecorder.state === "recording") {
      listening = false;
      try { mediaRecorder.stop(); } catch (e) { stopMic(); }
      btnMic.textContent = "…";
      return;
    }
    const text = (chatInput.value || "").trim();
    stopMic();
    if (text) sendChat(text);
    else setMicStatus("");
    return;
  }
  if (!canUseLiveMic()) {
    showHttpsHint();
    setMicStatus("Live mic is page par band hai. Voice note dabao.", true);
    if (chatVoiceFile) chatVoiceFile.click();
    return;
  }
  unlockAudio();
  if (window.speechSynthesis) window.speechSynthesis.cancel();
  try {
    if (isMobileShop() && window.MediaRecorder) {
      await startRecorder();
      return;
    }
    if (micSupported()) {
      try {
        const stream = await askMicPermission();
        stream.getTracks().forEach((t) => t.stop());
      } catch (permErr) {
        setMicStatus("Mic allow nahi hua. Browser popup mein Allow dabao.", true);
        return;
      }
      startWebSpeech();
      return;
    }
    if (window.MediaRecorder) {
      await startRecorder();
      return;
    }
    if (chatVoiceFile) chatVoiceFile.click();
    else alert("Yeh browser voice nahi sunta. Chrome use karo.");
  } catch (err) {
    setMicStatus((err && err.message) || "Mic start nahi hua", true);
    if (chatVoiceFile) chatVoiceFile.click();
  }
}

btnMic.addEventListener("click", () => {
  toggleMic();
});

if (chatVoiceFile) {
  chatVoiceFile.addEventListener("change", async () => {
    const file = (chatVoiceFile.files && chatVoiceFile.files[0]) || null;
    chatVoiceFile.value = "";
    if (!file) return;
    pendingFiles.push(file);
    showFiles();
    setMicStatus("Voice note bhej raha hoon…");
    await sendChat((chatInput.value || "").trim());
    setMicStatus("");
  });
}

if (btnSound) {
  btnSound.addEventListener("click", () => {
    const next = soundOn() ? "0" : "1";
    localStorage.setItem("assistantSoundOn", next);
    renderSoundButton();
    unlockAudio();
    if (next === "1") {
      speakText("Sound on hai. Ab assistant jawab bol kar sunayega.");
    } else if (window.speechSynthesis) {
      window.speechSynthesis.cancel();
    }
  });
}

if (window.speechSynthesis) {
  window.speechSynthesis.addEventListener("voiceschanged", () => {
    pickVoice(currentVoiceLang());
  });
}

document.querySelectorAll('input[name="voice-lang"]').forEach((el) => {
  el.addEventListener("change", () => applyVoiceLang(el.value));
});
applyVoiceLang(localStorage.getItem("assistantVoiceLang") || "hi-IN");
renderSoundButton();
showHttpsHint();

function applyKeyStatus(data) {
  const test = data.test || null;
  const testMsg = document.getElementById("or-test-msg");
  if (btnOrMenu) {
    const dot = btnOrMenu.querySelector("span");
    if (dot) {
      dot.classList.toggle("bg-emerald-400", !!(data.saved || data.configured));
      dot.classList.toggle("bg-amber-400", !(data.saved || data.configured));
    }
  }
  if (data.saved || data.configured) {
    keyStatus.textContent = data.configured
      ? "OpenRouter key save hai · " + (data.model || "")
      : "Save try hua";
    keyStatus.className = "text-sm text-emerald-300";
  } else {
    keyStatus.textContent = "Pehle OpenRouter API key save karo";
    keyStatus.className = "text-sm text-amber-300";
  }
  if (testMsg) {
    const bits = [];
    if (data.saved) {
      bits.push("Key save ho gayi" + (data.saved_to && data.saved_to.length ? " (" + data.saved_to.join(", ") + ")" : "") + ".");
    }
    if (test && test.ok) {
      bits.push("Internet test OK.");
    } else if (test && test.error) {
      bits.push("Save alag hai. Test: " + test.error);
    }
    if (data.curl === false) {
      bits.push("PHP curl band hai — php.ini mein extension=curl on karo.");
    }
    testMsg.textContent = bits.join(" ");
    testMsg.className = "mt-2 text-sm " + (data.saved || data.configured ? "text-emerald-300" : "text-rose-300");
  }
  if (data.model) document.getElementById("or-model").placeholder = data.model;
}

async function postKeyForm(extra) {
  const form = document.getElementById("or-key-form");
  const fd = new FormData(form);
  Object.keys(extra || {}).forEach((k) => {
    if (extra[k] === "" || extra[k] == null) {
      fd.delete(k);
    } else {
      fd.set(k, extra[k]);
    }
  });
  const typed = (fd.get("openrouter_api_key") || "").toString().trim();
  if (!typed) {
    fd.delete("openrouter_api_key");
  }
  const response = await fetch(appUrl("/api/assistant.php"), {
    method: "POST",
    headers: { "X-Requested-With": "fetch", Accept: "application/json" },
    body: fd,
  });
  const text = await response.text();
  let data = {};
  try {
    data = JSON.parse(text);
  } catch (e) {
    throw new Error(text ? text.slice(0, 180) : "Server ne JSON nahi diya (HTTP " + response.status + ")");
  }
  if (!response.ok) {
    throw new Error(data.error || "Save fail (HTTP " + response.status + ")");
  }
  return data;
}

async function saveOrTestKey(mode) {
  const typed = document.getElementById("or-key").value.trim();
  const extra = {};
  if (mode === "save") {
    if (!typed) {
      alert("Pehle OpenRouter key paste karo (sk-or-v1-...).");
      return;
    }
    extra.skip_test = "1";
  } else {
    extra.test_openrouter = "1";
    extra.skip_test = "";
    if (!typed) {
      extra.test_openrouter = "1";
    }
  }
  const data = await postKeyForm(extra);
  applyKeyStatus(data);
  if (mode === "save") {
    if (data.saved || data.configured) {
      window.location.href = appUrl("assistant.php?key=saved");
      return;
    }
    alert(data.error || "Key save nahi hui");
  } else if (data.test && data.test.ok) {
    alert("Key test OK");
  } else {
    alert((data.test && data.test.error) || "Key test fail");
  }
}

document.getElementById("or-key-form").addEventListener("submit", async (ev) => {
  ev.preventDefault();
  try {
    await saveOrTestKey("save");
  } catch (err) {
    alert(err.message);
  }
});

document.getElementById("btn-test-key").addEventListener("click", async () => {
  try {
    await saveOrTestKey("test");
  } catch (err) {
    alert(err.message);
  }
});

api("/api/assistant.php").then((data) => {
  applyKeyStatus(data);
}).catch((err) => {
  keyStatus.textContent = err.message;
});

addBubble("assistant", "Namaste 🙏 Main aapka Shop Assistant hoon. Neeche diye buttons ek tap mein kaam kara denge — ya likho / bolke batao: sale, purchase bill photo, ledger, due reminder. Main software mein sab save kar dunga.");
