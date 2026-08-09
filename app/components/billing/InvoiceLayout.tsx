type Product = {
  name: string;
  qty: string;
  unit: string;
  price: string;
};

type InvoiceLayoutProps = {
  customerName: string;
  mobile: string;
  products: Product[];
  grandTotal: number;
  invoiceNo?: string;
  billDate?: string;
};

export default function InvoiceLayout({
  customerName,
  mobile,
  products,
  grandTotal,
  invoiceNo,
  billDate,
}: InvoiceLayoutProps) {
  const validProducts = products.filter(
    (p) =>
      p.name.trim() !== "" &&
      Number(p.qty) > 0 &&
      Number(p.price) > 0
  );

  return (
    
  <div className="print-area mx-auto max-w-4xl rounded-lg bg-white p-8 shadow-lg text-black">
      {/* Header */}
      <div className="border-b-2 border-green-700 pb-4 text-center">
        <h1 className="text-3xl font-bold text-green-700">
          SATYANARAYAN HARDWARE STORES
        </h1>

       <p className="text-gray-700">
  Main Road, Jayanagar...
</p>

        <p  className="text-gray-700">
          Second Branch - Near Anumandal Hospital, Jayanagar
        </p>

        <p  className="text-gray-700">📞 9431875263 | 9831046765</p>

        <p  className="text-gray-700">✉️ sunnynayak01@gmail.com</p>

        <p  className="text-gray-700">
          GSTIN : 10ADTPN8807A1ZP
        </p>
      </div>

      {/* Invoice Info */}

      <div className="mt-6 flex justify-between">

        <div>
          <p>
            <strong>Invoice No :</strong> {invoiceNo ?? "Draft"}
          </p>

          <p>
            <strong>Date :</strong>{" "}
            {billDate ? new Date(`${billDate}T00:00:00`).toLocaleDateString("en-IN") : new Date().toLocaleDateString("en-IN")}
          </p>
        </div>

        <div className="text-right">
          <p>
            <strong>Customer :</strong> {customerName}
          </p>

          <p>
            <strong>Mobile :</strong> {mobile}
          </p>
        </div>

      </div>

      {/* Product Table */}

      <table className="mt-8 w-full border-collapse border">

        <thead>

          <tr className="bg-gray-200 text-black">

            <th className="border p-2">#</th>

            <th className="border p-2">
              Product
            </th>

            <th className="border p-2">
              Qty
            </th>

            <th className="border p-2">
              Unit
            </th>

            <th className="border p-2">
              Rate
            </th>

            <th className="border p-2">
              Amount
            </th>

          </tr>

        </thead>

        <tbody>

          {validProducts.map((p, i) => (

            <tr key={i}>

              <td className="border p-2 text-black">
                {i + 1}
              </td>

              <td className="border p-2">
                {p.name}
              </td>

              <td className="border p-2 text-black">
                {p.qty}
              </td>

              <td className="border p-2 text-center">
                {p.unit}
              </td>

              <td className="border p-2 text-right">
                ₹{p.price}
              </td>

              <td className="border p-2 text-right">
                ₹{Number(p.qty) * Number(p.price)}
              </td>

            </tr>

          ))}

        </tbody>

      </table>

      {/* Grand Total */}

      <div className="mt-8 flex justify-end">

        <div className="w-72 border p-4">

          <div className="flex justify-between text-xl font-bold">

            <span>Grand Total</span>

            <span>₹{grandTotal}</span>

          </div>

        </div>

      </div>

    </div>
  );
}
