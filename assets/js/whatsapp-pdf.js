function whatsappDigitsJs(phone) {
  const n = String(phone || "").replace(/\D+/g, "");
  if (!n) return "";
  if (n.length === 10) return "91" + n;
  if (n.length === 11 && n.startsWith("0")) return "91" + n.slice(1);
  if (n.length === 12 && n.startsWith("91")) return n;
  if (n.length > 12 && n.startsWith("91")) return n.slice(0, 12);
  return n;
}

function whatsappChatUrl(phone, text) {
  const n = whatsappDigitsJs(phone);
  if (!n) return "";
  const msg = String(text || "");
  if (msg === "") return "https://wa.me/" + n;
  return "https://wa.me/" + n + "?text=" + encodeURIComponent(msg);
}

function downloadBlobFile(blob, filename) {
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = filename;
  a.rel = "noopener";
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 4000);
}

function invoiceCssHref() {
  const link = document.querySelector('link[href*="invoice-print.css"]');
  if (link && link.href) return link.href;
  if (typeof appUrl === "function") {
    return window.location.origin + appUrl("assets/css/invoice-print.css");
  }
  return "assets/css/invoice-print.css";
}

function pdfLib() {
  if (window.jspdf && window.jspdf.jsPDF) return window.jspdf.jsPDF;
  if (window.jsPDF) return window.jsPDF;
  return null;
}

function waitFrame(iframe) {
  return new Promise((resolve) => {
    const done = () => resolve();
    iframe.addEventListener("load", done, { once: true });
    setTimeout(done, 600);
  });
}

async function invoiceElementToPdfBlob(sourceEl, filename) {
  if (typeof html2canvas !== "function") {
    throw new Error("PDF library load nahi hui. Ctrl+F5 karke refresh karo.");
  }
  const JsPDF = pdfLib();
  if (!JsPDF) {
    throw new Error("PDF engine nahi mili. Ctrl+F5 karke refresh karo.");
  }

  const sheet = sourceEl.classList && sourceEl.classList.contains("invoice")
    ? sourceEl
    : (sourceEl.querySelector && sourceEl.querySelector(".invoice")) || sourceEl;

  const iframe = document.createElement("iframe");
  iframe.setAttribute("aria-hidden", "true");
  iframe.style.cssText = "position:fixed;left:-4000px;top:0;width:794px;height:1400px;border:0;background:#fff;";
  document.body.appendChild(iframe);

  const extraCss = Array.from(document.querySelectorAll("style"))
    .map((s) => s.textContent || "")
    .join("\n");
  const doc = iframe.contentDocument;
  doc.open();
  doc.write(
    "<!DOCTYPE html><html><head><meta charset=\"UTF-8\">" +
      "<link rel=\"stylesheet\" href=\"" + invoiceCssHref() + "\">" +
      "<style>html,body{background:#fff!important;margin:0;padding:12px;} .no-print{display:none!important;} .invoice,.wrap,#ledger-sheet{width:190mm;max-width:100%;margin:0 auto;background:#fff;} " + extraCss + "</style>" +
      "</head><body class=\"invoice-page\">" + sheet.outerHTML + "</body></html>"
  );
  doc.close();
  await waitFrame(iframe);
  if (doc.fonts && doc.fonts.ready) {
    try { await doc.fonts.ready; } catch (e) { /* ignore */ }
  }
  await new Promise((r) => setTimeout(r, 120));

  const target = doc.querySelector(".invoice") || doc.body;
  iframe.style.height = Math.max(target.scrollHeight + 40, 400) + "px";

  try {
    const canvas = await html2canvas(target, {
      scale: 2,
      useCORS: true,
      logging: false,
      backgroundColor: "#ffffff",
      windowWidth: 794,
      scrollX: 0,
      scrollY: 0,
    });

    const pdf = new JsPDF({ unit: "mm", format: "a4", orientation: "portrait" });
    const pageW = pdf.internal.pageSize.getWidth();
    const pageH = pdf.internal.pageSize.getHeight();
    const margin = 8;
    const imgW = pageW - margin * 2;
    const imgH = (canvas.height * imgW) / canvas.width;
    const img = canvas.toDataURL("image/jpeg", 0.95);
    const pageInner = pageH - margin * 2;

    let heightLeft = imgH;
    let y = margin;
    pdf.addImage(img, "JPEG", margin, y, imgW, imgH);
    heightLeft -= pageInner;
    while (heightLeft > 3) {
      y = margin - (imgH - heightLeft);
      pdf.addPage();
      pdf.addImage(img, "JPEG", margin, y, imgW, imgH);
      heightLeft -= pageInner;
    }
    const blob = pdf.output("blob");
    if (filename) {
      try { pdf.setProperties({ title: filename.replace(/\.pdf$/i, "") }); } catch (e) { /* ignore */ }
    }
    return blob;
  } finally {
    iframe.remove();
  }
}

function openWhatsAppUrl(url) {
  let opened = false;
  try {
    const w = window.open(url, "_blank");
    opened = !!(w && !w.closed);
  } catch (err) {
    opened = false;
  }
  if (opened) return;
  setTimeout(() => {
    if (document.visibilityState === "visible") {
      window.location.href = url;
    }
  }, 900);
}

function absoluteUrl(pathOrUrl) {
  try {
    return new URL(pathOrUrl, window.location.href).href;
  } catch (err) {
    return String(pathOrUrl || "");
  }
}

async function uploadPdfBlob(blob, filename) {
  let name = String(filename || "Invoice.pdf");
  if (!/\.pdf$/i.test(name)) name += ".pdf";
  const base = typeof appUrl === "function"
    ? appUrl("api/invoice-pdf.php")
    : "api/invoice-pdf.php";
  const res = await fetch(base + "?name=" + encodeURIComponent(name), {
    method: "POST",
    headers: { "Content-Type": "application/pdf" },
    body: blob,
  });
  let data = {};
  try { data = await res.json(); } catch (err) { data = {}; }
  if (!res.ok || !data.ok) throw new Error(data.error || "PDF server par save nahi hui");
  return data;
}

async function copyToClipboard(text) {
  try {
    await navigator.clipboard.writeText(text);
    return true;
  } catch (err) {
    try {
      const ta = document.createElement("textarea");
      ta.value = text;
      ta.style.cssText = "position:fixed;left:-9999px;";
      document.body.appendChild(ta);
      ta.select();
      const ok = document.execCommand("copy");
      ta.remove();
      return ok;
    } catch (e) {
      return false;
    }
  }
}

async function shareInvoicePdfToWhatsApp(options) {
  const sourceEl = options.element;
  const filename = options.filename || "Invoice.pdf";
  let phone = String(options.phone || "").trim();

  if (!sourceEl) {
    throw new Error("Invoice preview nahi mili.");
  }
  if (!whatsappDigitsJs(phone)) {
    phone = window.prompt("Customer ka WhatsApp number (10 digit ya 91 ke saath):\nNumber save na ho tab bhi chalega.", phone) || "";
  }
  if (!whatsappDigitsJs(phone)) {
    throw new Error("WhatsApp number nahi mila. Customer mobile save karo.");
  }

  const blob = await invoiceElementToPdfBlob(sourceEl, filename);
  let file = null;
  try {
    file = new File([blob], filename, { type: "application/pdf" });
  } catch (err) {
    file = null;
  }

  // Best case: PDF FILE seedha WhatsApp ko — koi text message nahi
  if (file && window.isSecureContext && navigator.canShare && navigator.canShare({ files: [file] })) {
    try {
      await navigator.share({ files: [file] });
      return "shared";
    } catch (err) {
      if (err && err.name === "AbortError") return "cancel";
    }
  }

  const url = whatsappChatUrl(phone, "");
  if (!url) return "cancel";

  let savedUrl = "";
  try {
    const up = await uploadPdfBlob(blob, filename);
    if (up && up.url) savedUrl = absoluteUrl(up.url);
  } catch (err) {
    /* server save fail */
  }

  if (savedUrl) await copyToClipboard(savedUrl);
  openWhatsAppUrl(url);

  if (window.isSecureContext && savedUrl) {
    downloadBlobFile(blob, filename);
    return "download";
  }
  return "text-only";
}

async function bindWhatsAppPdfButton(btn, getContext) {
  if (!btn) return;
  btn.addEventListener("click", async (event) => {
    event.preventDefault();
    const original = btn.textContent;
    btn.disabled = true;
    btn.textContent = "PDF bana rahe hain…";
    try {
      const ctx = typeof getContext === "function" ? getContext() : getContext;
      const result = await shareInvoicePdfToWhatsApp(ctx);
      if (result === "download") {
        alert("WhatsApp chat khul gayi (koi text nahi likha).\n📎 dabao → Document → Downloads wali Invoice PDF choose karke bhejo.\nPDF ka link clipboard mein bhi copy hai — paste karke bhi bhej sakte ho.");
      } else if (result === "text-only") {
        alert("WhatsApp chat khul gayi aur PDF ka LINK clipboard mein copy ho gaya.\nChat mein paste karke bhejo — customer link tap karke PDF kholega.\n\n(Seedha file bhejne ke liye site par HTTPS chahiye.)");
      }
    } catch (err) {
      alert(err.message || String(err));
    } finally {
      btn.disabled = false;
      btn.textContent = original;
    }
  });
}

window.whatsappDigitsJs = whatsappDigitsJs;
window.shareInvoicePdfToWhatsApp = shareInvoicePdfToWhatsApp;
window.bindWhatsAppPdfButton = bindWhatsAppPdfButton;
window.invoiceElementToPdfBlob = invoiceElementToPdfBlob;
