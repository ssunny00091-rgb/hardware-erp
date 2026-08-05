import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";
import { company } from "../data/company";
import { generateInvoiceNumber } from "./invoiceNumber";

type Product = {
  name: string;
  qty: string;
  unit: string;
  price: string;
};

export async function downloadInvoice(
  customerName: string,
  mobile: string,
  products: Product[],
  grandTotal: number
) {

  // ==========================
  // Create PDF
  // ==========================

  const doc = new jsPDF();
 


  // ==========================
  // Page Border
  // ==========================

  doc.setDrawColor(0, 128, 0);
  doc.setLineWidth(0.8);

  doc.rect(8, 8, 194, 281);

  doc.setDrawColor(180);
  doc.setLineWidth(0.2);

  doc.rect(10, 10, 190, 277);



  // ==========================
// Invoice Information
// ==========================

const invoiceNumber = generateInvoiceNumber();

// ==========================
// Company Header
// ==========================


doc.setTextColor(0, 100, 0);
doc.setFont("helvetica", "bold");
doc.setFontSize(20);

doc.text(company.name, 105, 18, {
  align: "center",
});

doc.setTextColor(0, 0, 0);

doc.setFont("helvetica", "normal");
doc.setFontSize(10);

doc.text(company.address, 105, 28, {
  align: "center",
});

doc.text(`Phone : ${company.mobile}`, 105, 38, {
  align: "center",
});

doc.text(`Email : ${company.email}`, 105, 44, {
  align: "center",
});

doc.text(`GSTIN : ${company.gst}`, 105, 50, {
  align: "center",
});
// ==========================
// Customer Details
// ==========================

doc.setFont("helvetica", "bold");
doc.text("Customer Details", 14, 80);

doc.setFont("helvetica", "normal");

doc.text(`Customer : ${customerName}`, 14, 88);

doc.text(`Mobile : ${mobile}`, 14, 94);

doc.line(14, 100, 196, 100);

// ==========================
// Product Table
// ==========================

autoTable(doc, {
  startY: 108,

 head: [["#", "Product", "Qty", "Unit", "Price", "Total"]],

body: products
  .filter(
    (product) =>
      product.name.trim() !== "" &&
      Number(product.qty) > 0 &&
      Number(product.price) > 0
  )
  .map((product, index) => [
    index + 1,
    product.name,
    product.qty,
    product.unit,
    `Rs. ${product.price}`,
    `Rs. ${Number(product.qty) * Number(product.price)}`,
  ]),
  theme: "grid",

  headStyles: {
  fillColor: [0, 102, 204], // Blue Header
  textColor: [255, 255, 255],
  fontStyle: "bold",
  fontSize: 11,
  halign: "center",
  valign: "middle",
},

bodyStyles: {
  fontSize: 10,
  textColor: [0, 0, 0],
  valign: "middle",
},

columnStyles: {
  0: {
    cellWidth: 12,
    halign: "center",
  },
  1: {
    cellWidth: 65,
    halign: "left",
  },
  2: {
    cellWidth: 18,
    halign: "center",
  },
  3: {
    cellWidth: 22,
    halign: "center",
  },
  4: {
    cellWidth: 35,
    halign: "right",
  },
  5: {
    cellWidth: 35,
    halign: "right",
  },
},
});

  // ==========================
  // Grand Total
  // ==========================
  const finalY = (doc as jsPDF & { lastAutoTable: { finalY: number } }).lastAutoTable.finalY + 10;

doc.setFillColor(34, 139, 34);

doc.rect(130, finalY, 66, 12, "F");

doc.setTextColor(255, 255, 255);

doc.setFont("helvetica", "bold");

doc.setFontSize(12);

doc.text(`Grand Total : Rs. ${grandTotal}`, 133, finalY + 8);

doc.setTextColor(0, 0, 0);
doc.setFontSize(11);

doc.setFont("helvetica", "italic");

doc.text(
  "Thank You! Visit Again.",
  105,
  finalY + 28,
  {
    align: "center",
  }
);


  // Total Box

  // ==========================
  // Footer
  // ==========================

  // Thank You
  // Visit Again

  // ==========================
  // Save PDF
  // ==========================

  doc.save(`${invoiceNumber}.pdf`);
}
