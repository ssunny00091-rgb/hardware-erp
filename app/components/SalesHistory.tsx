type Sale = {
  id: number;
  customer_name: string;
  mobile: string;
  total: number;
  created_at: string;
};

type SalesHistoryProps = {
  open: boolean;
  sales: Sale[];
  onClose: () => void;
};

export default function SalesHistory({
  open,
  sales,
  onClose,
}: SalesHistoryProps) {
  if (!open) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
      <div className="w-[95%] max-w-6xl rounded-xl bg-white p-6 shadow-2xl">

        <div className="mb-5 flex items-center justify-between">
          <h2 className="text-3xl font-bold text-black">
            🧾 Sales History
          </h2>

          <button
            onClick={onClose}
            className="rounded-lg bg-red-600 px-5 py-2 text-white"
          >
            ✖ Close
          </button>
        </div>

        <table className="w-full border-collapse border">

          <thead>

            <tr className="bg-blue-600 text-white">

              <th className="border p-3">Customer</th>

              <th className="border p-3">Mobile</th>

              <th className="border p-3">Total</th>

              <th className="border p-3">Date</th>

              <th className="border p-3">Action</th>

            </tr>

          </thead>

          <tbody>

            {sales.map((sale) => (

              <tr key={sale.id}>

                <td className="border p-3 text-black">
                  {sale.customer_name}
                </td>

                <td className="border p-3 text-black">
                  {sale.mobile}
                </td>

                <td className="border p-3 text-black">
                  ₹{sale.total}
                </td>

                <td className="border p-3 text-black">
                  {new Date(
                    sale.created_at
                  ).toLocaleDateString("en-IN")}
                </td>

                <td className="border p-3">

                  <div className="flex justify-center gap-2">

                    <button className="rounded bg-blue-600 px-3 py-1 text-white">
                      👁
                    </button>

                    <button className="rounded bg-red-600 px-3 py-1 text-white">
                      🗑
                    </button>

                  </div>

                </td>

              </tr>

            ))}

          </tbody>

        </table>

      </div>
    </div>
  );
}