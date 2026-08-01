"use client";
import { products as productMaster } from "./data/products";
import { useState } from "react";
import DashboardCard from "./components/DashboardCard";
import ProductRow from "./components/ProductRow";
import BillSummary from "./components/BillSummary";
import { downloadInvoice } from "./utils/downloadInvoice";
import { generateInvoiceNumber } from "./utils/invoiceNumber";
import { customers } from "./data/customers";
import InvoicePreview from "./components/InvoicePreview";
import { supabase } from "./lib/supabase";
import { useEffect } from "react";
import SalesHistory from "./components/SalesHistory";
import CustomerForm from "./components/CustomerForm";
export default function Home() {
  

  /* =========================
        State Management
  ========================= */

  const [showForm, setShowForm] = useState(false);
  const [showPreview, setShowPreview] = useState(false);

  const [products, setProducts] = useState([
    {
      name: "",
      qty: "",
       unit: "Piece",
      price: "",
    },
  ]);

  const [CustomerName, setCustomerName] = useState("");
  const [mobile, setMobile] = useState("");
  const [address, setAddress] = useState("");
const [gst, setGst] = useState("");
  const handleMobileChange = (value: string) => {
  setMobile(value);

  const customer = customers.find(
    (item) => item.mobile === value
  );

  if (customer) {
  setCustomerName(customer.name);
  setAddress(customer.address);
  setGst(customer.gst);
}
};
const [sales, setSales] = useState<any[]>([]);
const [showSalesHistory, setShowSalesHistory] = useState(false);



  /* =========================
        Product Functions
  ========================= */

  // Update Product
const handleProductChange = (
  index: number,
  field: "name" | "qty" | "unit" | "price",
  value: string
) => {
  setProducts((prevProducts) => {
    const updatedProducts = [...prevProducts];

    updatedProducts[index] = {
      ...updatedProducts[index],
      [field]: value,
    };

    // Product select hone par auto-fill
    if (field === "name") {
      const selectedProduct = productMaster.find(
        (item) => item.name === value
      );

      if (selectedProduct) {
        updatedProducts[index].price = selectedProduct.price.toString();
        updatedProducts[index].unit = selectedProduct.unit;
      }
    }

    return updatedProducts;
  });
};

  // Delete Product
  const deleteProduct = (index: number) => {
    const updatedProducts = products.filter((_, i) => i !== index);
    setProducts(updatedProducts);
  };
  /* =========================
      Grand Total
========================= */

const grandTotal = products.reduce((total, product) => {
  return total + Number(product.qty) * Number(product.price);
}, 0);

const invoiceNumber = generateInvoiceNumber();

console.log(
  "Product 0 Name:",
  products[0]?.name,
  "Price:",
  products[0]?.price,
  "Qty:",
  products[0]?.qty
);

/* =========================
      Fetch Sales
========================= */

const fetchSales = async () => {
  const { data, error } = await supabase
    .from("sales")
    .select("*")
    .order("created_at", { ascending: false });

  if (error) {
    console.error(error);
    alert("Failed to load sales.");
    return;
  }

  setSales(data || []);
};

/* =========================
      Save Sale
========================= */

const saveSale = async () => {
  const validProducts = products.filter(
    (p) =>
      p.name.trim() !== "" &&
      Number(p.qty) > 0 &&
      Number(p.price) > 0
  );

  const { data, error } = await supabase
    .from("sales")
    .insert([
      {
        customer_name: CustomerName,
        mobile: mobile,
        total: grandTotal,
        products: validProducts,
      },
    ])
    .select();

  if (error) {
    console.error(error);
    alert(error.message);
    return;
  }

  console.log(data);

  alert("✅ Sale Saved Successfully");

  // Refresh Sales List
  fetchSales();
};

/* =========================
      Load Sales
========================= */

useEffect(() => {
  fetchSales();
}, []);
  /* =========================
        UI
  ========================= */

  return (
    <main className="min-h-screen bg-gray-900 p-6">

      {/* =========================
            Page Heading
      ========================= */}

      <h1 className="mb-6 text-4xl font-bold text-white">
        🏪 SATYANARYAN HARDWARE STORES
      </h1>



      {/* =========================
            Action Buttons
      ========================= */}

      <div className="mb-6 flex gap-4">

        <button
          type="button"
          onClick={() => {
            setShowForm(true);

            setProducts([
  {
    name: "",
    qty: "",
    unit: "Piece",
    price: "",
  },
]);
          }}
          className="rounded-lg bg-green-600 px-5 py-3 text-white hover:bg-green-700"
        >
          ➕ New Sale
        </button>


        

        <button
          type="button"
          onClick={() => alert("Upload Bill Button Clicked")}
          className="rounded-lg bg-blue-600 px-5 py-3 text-white hover:bg-blue-700"
        >
          📷 Upload Bill
        </button>
        <button
  type="button"
  onClick={() => {
    fetchSales();
    setShowSalesHistory(true);
  }}
  className="rounded-lg bg-indigo-600 px-5 py-3 text-white hover:bg-indigo-700"
>
  🧾 Sales History
</button>
        

      </div>



      {/* =========================
            Dashboard Cards
      ========================= */}

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

        <DashboardCard title="Today's Sales" value="₹25,000" />
        <DashboardCard title="Today's Purchase" value="₹12,000" />
        <DashboardCard title="Cash in Hand" value="₹8,000" />
        <DashboardCard title="Pending Payment" value="₹15,000" />

      </div>



      {/* =========================
            New Sale Form
      ========================= */}

      {showForm && (
        
        



        <div className="mt-8 rounded-xl bg-black p-6 shadow-lg">

          <h2 className="mb-4 text-2xl font-bold text-white">
            📝 New Sale Form
          </h2>



          <CustomerForm
  customerName={CustomerName}
  mobile={mobile}
  address={address}
  gst={gst}
  onCustomerNameChange={setCustomerName}
  onMobileChange={handleMobileChange}
  onAddressChange={setAddress}
  onGstChange={setGst}
/>



          {/* =========================
                Product Table Header
          ========================= */}

          <div className="mb-3 grid grid-cols-5 gap-3 rounded-lg bg-gray-500 p-3 font-bold">

            <div>Product Name</div>
            <div>Qty</div>
            <div>Price</div>
            <div>Total</div>
            <div>Action</div>

          </div>



          {/* =========================
                Product List
          ========================= */}
          
          {products.map((product, index) => (

            <ProductRow
  key={index}
  index={index}
  product={product}
  onChange={handleProductChange}
  total={Number(product.qty) * Number(product.price)}
  onDelete={deleteProduct}
  onAddNewRow={() => {
    setProducts([
      ...products,
      {
        name: "",
        qty: "",
        unit: "Piece",
        price: "",
      },
    ]);
  }}
/>

          ))}
         <div className="mb-6 flex items-center justify-between rounded-lg bg-gray-100 p-4">

  <div className="text-lg font-semibold text-gray-700">
    📦 Products Added : {products.length}
  </div>

  <div className="flex gap-3">

    <button
      type="button"
      onClick={() => {
        setProducts([
          ...products,
          {
            name: "",
            qty: "",
            unit: "Piece",
            price: "",
          },
        ]);
      }}
      className="rounded-lg bg-purple-600 px-6 py-3 text-white hover:bg-purple-700"
    >
      ➕ Add Product
    </button>

    <button
      type="button"
      onClick={() => {
        if (products.length > 1) {
          setProducts(products.slice(0, -1));
        }
      }}
      className="rounded-lg bg-red-600 px-6 py-3 text-white hover:bg-red-700"
    >
      🗑️ Remove Last Product
    </button>

  </div>

</div>
        {/* =========================
               Grand Total
           ========================= */}

           <div className="mb-6 flex justify-end">
           <div className="rounded-lg bg-green-700 px-6 py-4 text-xl font-bold text-white">
            Grand Total : ₹{grandTotal}
        </div>
       </div>
           {/* =========================
                     Bill Summary
               ========================= */}

          <BillSummary
  invoiceNumber={invoiceNumber}
  customerName={CustomerName}
  mobile={mobile}
  products={products}
  grandTotal={grandTotal}
/>
          {/* =========================
                Save Button
          ========================= */}

   <button
  onClick={() => setShowPreview(true)}

            className="rounded-lg bg-green-600 px-5 py-3 text-white hover:bg-green-700"
          >
            💾 Save Sale
          </button>

        </div>

      )}
<SalesHistory
  open={showSalesHistory}
  sales={sales}
  onClose={() => setShowSalesHistory(false)}
/>
<InvoicePreview
  open={showPreview}
  onClose={() => setShowPreview(false)}
  customerName={CustomerName}
  mobile={mobile}
  products={products}
  grandTotal={grandTotal}
  onSave={saveSale}
  onDownload={async () => {
    await downloadInvoice(
      CustomerName,
      mobile,
      products,
      grandTotal
      
    );
  }}
/>
    </main>
  );
}