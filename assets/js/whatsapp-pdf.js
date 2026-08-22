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
      if (err && err.name === "AbortError") return "cancel";
    }
  }

  downloadBlobFile(blob, filename);
  const url = whatsappChatUrl(phone, "");
  if (url) window.open(url, "_blank");
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
        alert("Sirf PDF download hua. WhatsApp mein 📎 Document se yeh PDF attach karo — text mat likho.");
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
