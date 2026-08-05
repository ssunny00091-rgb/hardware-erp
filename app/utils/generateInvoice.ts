import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";
import "../fonts/NotoSans-Regular";
type Product = {
  name: string;
  qty: string;
  price: string;
};

export function generateInvoice(
  customerName: string,
  mobile: string,
  products: Product[],
  grandTotal: number
) {
  const doc = new jsPDF();

  doc.setFontSize(18);
  doc.text("SATYANARAYAN HARDWARE STORES", 15, 20);

  doc.setFontSize(12);
  doc.text(`Customer : ${customerName}`, 15, 35);
  doc.text(`Mobile : ${mobile}`, 15, 43);
  doc.text(`Date : ${new Date().toLocaleDateString()}`, 15, 51);

  autoTable(doc, {
    startY: 60,
    head: [["Product", "Qty", "Price", "Total"]],
    body: products.map((product) => [
      product.name,
      product.qty,
      `₹${product.price}`,
      `₹${Number(product.qty) * Number(product.price)}`,
    ]),
  });

  const finalY = (doc as jsPDF & { lastAutoTable: { finalY: number } }).lastAutoTable.finalY;

  doc.setFontSize(14);
  doc.text(`Grand Total : ₹${grandTotal}`, 15, finalY + 15);

  doc.save("Invoice.pdf");
}
