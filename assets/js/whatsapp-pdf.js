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
  let msg = String(text || "");
  if (msg.length > 3500) {
    msg = msg.slice(0, 3400) + "\n\n...baaki detail bill PDF mein hai.";
  }
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

function cloneInvoiceForPdf(sourceEl) {
  const wrap = document.createElement("div");
  wrap.className = "invoice-page";
  wrap.style.cssText = "position:fixed;left:-12000px;top:0;width:190mm;background:#fff;z-index:-1;padding:0;margin:0;";
  const clone = sourceEl.cloneNode(true);
  clone.style.width = "190mm";
  clone.style.maxWidth = "190mm";
  clone.style.margin = "0";
  clone.style.background = "#fff";
  wrap.appendChild(clone);
  document.body.appendChild(wrap);
  return { wrap, clone };
}

async function invoiceElementToPdfBlob(sourceEl, filename) {
  if (typeof html2pdf !== "function") {
    throw new Error("PDF library load nahi hui. Page refresh karke phir try karo.");
  }
  const { wrap, clone } = cloneInvoiceForPdf(sourceEl);
  try {
    const opt = {
      margin: [8, 8, 8, 8],
      filename: filename,
      image: { type: "jpeg", quality: 0.98 },
      html2canvas: {
        scale: 2,
        useCORS: true,
        logging: false,
        backgroundColor: "#ffffff",
        windowWidth: 794,
      },
      jsPDF: { unit: "mm", format: "a4", orientation: "portrait" },
      pagebreak: { mode: ["css", "legacy"] },
    };
    let blob = await html2pdf().set(opt).from(clone).outputPdf("blob");
    if (blob instanceof Uint8Array) {
      blob = new Blob([blob], { type: "application/pdf" });
    }
    if (!(blob instanceof Blob)) {
      throw new Error("PDF ban nahi paya.");
    }
    return blob;
  } finally {
    wrap.remove();
  }
}

async function shareInvoicePdfToWhatsApp(options) {
  const sourceEl = options.element;
  const filename = options.filename || "Invoice.pdf";
  let phone = String(options.phone || "").trim();
  const caption = String(options.caption || filename);

  if (!sourceEl) {
    throw new Error("Invoice preview nahi mili.");
  }
  if (!whatsappDigitsJs(phone)) {
    phone = window.prompt("Customer ka WhatsApp number (10 digit):", phone) || "";
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

  if (file && navigator.canShare && navigator.canShare({ files: [file] })) {
    try {
      await navigator.share({
        files: [file],
        title: filename,
        text: caption,
      });
      return "shared";
    } catch (err) {
      if (err && err.name === "AbortError") {
        return "cancel";
      }
    }
  }

  downloadBlobFile(blob, filename);
  const extra =
    caption +
    "\n\nBill ka PDF (Invoice Preview jaisa) download ho gaya hai.\nWhatsApp mein 📎 Attach document se wahi PDF bhejein, phir Send dabayein.";
  const url = whatsappChatUrl(phone, extra);
  if (url) {
    window.open(url, "_blank");
  }
  return "download";
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
        alert("PDF save ho gaya. WhatsApp chat khul gaya — 📎 se wahi PDF attach karke customer ko bhejo.");
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
