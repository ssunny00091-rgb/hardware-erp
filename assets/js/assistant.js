const chatLog = document.getElementById("chat-log");
const chatInput = document.getElementById("chat-input");
const chatFile = document.getElementById("chat-file");
const chatFiles = document.getElementById("chat-files");
const btnSend = document.getElementById("btn-send");
const btnMic = document.getElementById("btn-mic");
const btnSaveKey = document.getElementById("btn-save-key");
const keyStatus = document.getElementById("assistant-key-status");

const history = [];
let pendingFiles = [];
let listening = false;
let recognition = null;
let busy = false;

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
  });
}

function speak(text) {
  if (!window.speechSynthesis || !text) return;
  window.speechSynthesis.cancel();
  const u = new SpeechSynthesisUtterance(text.slice(0, 400));
  u.lang = "hi-IN";
  u.rate = 1;
  window.speechSynthesis.speak(u);
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

function showFiles() {
  if (!pendingFiles.length) {
    chatFiles.classList.add("hidden");
    chatFiles.textContent = "";
    return;
  }
  chatFiles.classList.remove("hidden");
  chatFiles.textContent = pendingFiles.map((f) => f.name).join(", ");
}

chatFile.addEventListener("change", async () => {
  const list = Array.from(chatFile.files || []);
  pendingFiles = [];
  for (const file of list.slice(0, 6)) {
    pendingFiles.push(await compressImage(file));
  }
  showFiles();
});

async function sendChat(text) {
  const message = (text || chatInput.value || "").trim();
  if (busy) return;
  if (!message && !pendingFiles.length) return;
  busy = true;
  btnSend.disabled = true;
  const filesNow = pendingFiles.slice();
  addBubble("user", message || ("📎 " + filesNow.map((f) => f.name).join(", ")));
  chatInput.value = "";
  pendingFiles = [];
  showFiles();
  chatFile.value = "";

  const form = new FormData();
  form.append("payload", JSON.stringify({ message, history }));
  filesNow.forEach((file) => form.append("files[]", file, file.name));

  try {
    const response = await fetch(appUrl("/api/assistant.php"), { method: "POST", body: form });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(data.error || "Assistant fail");
    const reply = data.reply || "Ho gaya.";
    const extra = actionSummary(data.actions || []);
    addBubble("assistant", reply, extra, data.tables || []);
    history.push({ role: "user", content: message || "Photo/PDF bheji hai" });
    history.push({ role: "assistant", content: reply });
    speak(reply);
  } catch (err) {
    addBubble("assistant", "Error: " + err.message);
  } finally {
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

function micSupported() {
  return window.SpeechRecognition || window.webkitSpeechRecognition;
}

btnMic.addEventListener("click", () => {
  const Ctor = micSupported();
  if (!Ctor) {
    alert("Yeh browser voice nahi sunta. Chrome use karo, ya type karo.");
    return;
  }
  if (listening && recognition) {
    recognition.stop();
    return;
  }
  recognition = new Ctor();
  recognition.lang = "hi-IN";
  recognition.interimResults = true;
  recognition.continuous = false;
  listening = true;
  btnMic.textContent = "⏹ Stop";
  btnMic.classList.add("ring-2", "ring-white");
  recognition.onresult = (ev) => {
    let said = "";
    for (let i = 0; i < ev.results.length; i++) {
      said += ev.results[i][0].transcript;
    }
    chatInput.value = said.trim();
    if (ev.results[ev.results.length - 1].isFinal) {
      sendChat(said.trim());
    }
  };
  recognition.onerror = () => {
    listening = false;
    btnMic.textContent = "🎤 Bolke";
    btnMic.classList.remove("ring-2", "ring-white");
  };
  recognition.onend = () => {
    listening = false;
    btnMic.textContent = "🎤 Bolke";
    btnMic.classList.remove("ring-2", "ring-white");
  };
  recognition.start();
});

btnSaveKey.addEventListener("click", async () => {
  try {
    const data = await api("/api/assistant.php", {
      method: "POST",
      body: JSON.stringify({
        openrouter_api_key: document.getElementById("or-key").value.trim(),
        openrouter_model: document.getElementById("or-model").value.trim(),
      }),
    });
    keyStatus.textContent = data.configured ? "OpenRouter ready ✓" : "Key save hui, lekin khali hai";
    keyStatus.className = "text-sm " + (data.configured ? "text-emerald-300" : "text-amber-300");
    alert("Key save ho gayi");
  } catch (err) {
    alert(err.message);
  }
});

api("/api/assistant.php").then((data) => {
  keyStatus.textContent = data.configured
    ? "OpenRouter ready · " + (data.model || "")
    : "Pehle OpenRouter API key save karo (neeche)";
  keyStatus.className = "text-sm " + (data.configured ? "text-emerald-300" : "text-amber-300");
  if (data.model) document.getElementById("or-model").placeholder = data.model;
}).catch((err) => {
  keyStatus.textContent = err.message;
});

addBubble("assistant", "Namaste. Bolke sale banao, ya supplier bill ki photo daal do — main software mein save kar dunga.");
