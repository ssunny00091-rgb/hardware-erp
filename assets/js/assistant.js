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

function addBubble(role, text, extra) {
  const wrap = document.createElement("div");
  wrap.className = "chat-bubble " + (role === "user" ? "chat-user" : "chat-bot");
  wrap.innerHTML = "<div class='chat-role'>" + (role === "user" ? "Aap" : "Assistant") + "</div>" +
    "<div class='chat-text'></div>";
  wrap.querySelector(".chat-text").textContent = text;
  if (extra) {
    const meta = document.createElement("div");
    meta.className = "chat-meta";
    meta.textContent = extra;
    wrap.appendChild(meta);
  }
  chatLog.appendChild(wrap);
  chatLog.scrollTop = chatLog.scrollHeight;
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
    addBubble("assistant", reply, extra);
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
