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
  if (msg === "") {
    return "https://wa.me/" + n;
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
  wrap.setAttribute("data-pdf-capture", "1");
  wrap.style.cssText =
    "position:fixed;left:0;top:0;width:794px;max-width:794px;background:#fff;color:#000;z-index:2147483000;padding:16px;box-sizing:border-box;overflow:visible;";
  const clone = sourceEl.cloneNode(true);
  clone.style.width = "762px";
  clone.style.maxWidth = "762px";
  clone.style.margin = "0";
  clone.style.background = "#fff";
  clone.style.color = "#000";
  wrap.appendChild(clone);
  document.body.appendChild(wrap);
  return { wrap, clone };
}

async function invoiceElementToPdfBlob(sourceEl, filename) {
  if (typeof html2pdf !== "function") {
    throw new Error("PDF library load nahi hui. Ctrl+F5 karke page refresh karo.");
  }
  const { wrap, clone } = cloneInvoiceForPdf(sourceEl);
  try {
    await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));
    const opt = {
      margin: [8, 8, 8, 8],
      filename: filename,
      image: { type: "jpeg", quality: 0.95 },
      html2canvas: {
        scale: 2,
        useCORS: true,
        logging: false,
        backgroundColor: "#ffffff",
        scrollX: 0,
        scrollY: 0,
        windowWidth: 794,
      },
      jsPDF: { unit: "mm", format: "a4", orientation: "portrait" },
      pagebreak: { mode: ["css", "legacy"] },
    };
    const worker = html2pdf().set(opt).from(clone);
    let pdfDoc = null;
    try {
      pdfDoc = await worker.toPdf().get("pdf");
    } catch (err) {
      pdfDoc = null;
    }
    if (pdfDoc && typeof pdfDoc.output === "function") {
      const out = pdfDoc.output("blob");
      if (out instanceof Blob) return out;
    }
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
      await navigator.share({ files: [file], title: filename });
      return "shared";
    } catch (err) {
      if (err && err.name === "AbortError") {
        return "cancel";
      }
    }
  }

  downloadBlobFile(blob, filename);
  const url = whatsappChatUrl(phone, "");
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
        alert("PDF download ho gaya. Customer ko TEXT mat bhejo — WhatsApp mein 📎 se sirf yeh PDF attach karke Send karo.");
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
