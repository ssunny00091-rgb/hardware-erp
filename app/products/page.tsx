"use client";
import EditProductModal from "../components/products/EditProductModal";
import { useEffect, useState } from "react";
import { supabase } from "../lib/supabase";
import AddProductModal from "../components/products/AddProductModal";
type Product = {
  id: number;
  product_name: string;
  brand: string;
  category: string;
  unit: string;
  purchase_price: number;
  selling_price: number;
  stock: number;
  gst_percent: number;
  hsn_code: string;
};

export default function ProductsPage() {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [showAddModal, setShowAddModal] = useState(false);
  const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);
const [showEditModal, setShowEditModal] = useState(false);


  useEffect(() => {
    fetchProducts();
  }, []);
  const deleteProduct = async (id: number) => {
  const confirmDelete = confirm(
    "Are you sure you want to delete this product?"
  );

  if (!confirmDelete) return;

  const { error } = await supabase
    .from("products")
    .delete()
    .eq("id", id);

  if (error) {
    alert(error.message);
    return;
  }

  alert("✅ Product Deleted");

  fetchProducts();
};

  const fetchProducts = async () => {
    const { data, error } = await supabase
      .from("products")
      .select("*")
      .order("product_name");

    if (error) {
      console.error(error);
      alert(error.message);
      return;
    }

    setProducts(data || []);
    setLoading(false);
  };

  return (
    <main className="min-h-screen bg-slate-900 p-8 text-white">
      <div className="mx-auto max-w-7xl">

        <div className="mb-8 flex items-center justify-between">
          <h1 className="text-4xl font-bold">
            📦 Product Master
          </h1>

          <button
  onClick={() => setShowAddModal(true)}
  className="rounded-xl bg-green-600 px-5 py-3 font-semibold text-white transition hover:bg-green-500"
>
  ➕ Add Product
</button>
        </div>

        <div className="overflow-hidden rounded-2xl border border-white/20 bg-white/10 backdrop-blur-xl">

          <table className="w-full">

            <thead className="bg-white/10">

              <tr>

                <th className="p-4 text-left">Product</th>

                <th className="p-4 text-left">Brand</th>

                <th className="p-4 text-center">Stock</th>

                <th className="p-4 text-right">Price</th>

                <th className="p-4 text-center">Action</th>

              </tr>

            </thead>

            <tbody>

              {loading ? (

                <tr>

                  <td colSpan={4} className="p-8 text-center">
                    Loading...
                  </td>

                </tr>

              ) : (

                products.map((product) => (

                  <tr
                    key={product.id}
                    className="border-t border-white/10 hover:bg-white/5"
                  >
                    <td className="p-4">
                      {product.product_name}
                    </td>

                    <td className="p-4">
                      {product.brand}
                    </td>

                    <td className="p-4 text-center">
  {product.stock > 20 ? (
    <span className="rounded-full bg-green-600 px-3 py-1 text-sm font-semibold text-white">
      🟢 {product.stock}
    </span>
  ) : product.stock > 5 ? (
    <span className="rounded-full bg-yellow-500 px-3 py-1 text-sm font-semibold text-white">
      🟡 {product.stock}
    </span>
  ) : (
    <span className="rounded-full bg-red-600 px-3 py-1 text-sm font-semibold text-white">
      🔴 LOW ({product.stock})
    </span>
  )}
</td>

                    <td className="p-4 text-right">
                      ₹{product.selling_price}
                    </td>
<td className="p-4 text-center">

  <button
    onClick={() => {
      setSelectedProduct(product);
      setShowEditModal(true);
    }}
    className="mr-2 rounded-lg bg-yellow-500 px-3 py-2 text-white hover:bg-yellow-600"
  >
    ✏️
  </button>

  <button
  onClick={() => deleteProduct(product.id)}
    className="rounded-lg bg-red-500 px-3 py-2 text-white hover:bg-red-600"
  >
    🗑
  </button>

</td>
                  </tr>
                  

                ))

              )}

            </tbody>

          </table>

        </div>

      </div>
 <AddProductModal
  open={showAddModal}
  onClose={() => setShowAddModal(false)}
  onSaved={fetchProducts}
/>

<EditProductModal
  open={showEditModal}
  product={selectedProduct}
  onClose={() => setShowEditModal(false)}
  onSaved={fetchProducts}
/>


    </main>
  );
}