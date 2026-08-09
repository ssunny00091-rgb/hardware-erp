"use client";
import EditProductModal from "../components/products/EditProductModal";
import { useCallback, useEffect, useRef, useState } from "react";
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
  const [deletingId, setDeletingId] = useState<number | null>(null);

  const mounted = useRef(true);
  useEffect(() => {
    return () => {
      mounted.current = false;
    };
  }, []);

  const fetchProducts = useCallback(async () => {
    try {
      const { data, error } = await supabase
        .from("products")
        .select("*")
        .order("product_name", { ascending: true });

      if (error) {
        console.error("fetchProducts error:", error);
        // Replace alert with in-app toast/notification in production
        alert(error.message);
        return;
      }

      if (!mounted.current) return;
      setProducts(data || []);
    } catch (err) {
      console.error("Unexpected fetch error:", err);
      alert("Failed to fetch products");
    } finally {
      if (mounted.current) setLoading(false);
    }
  }, []);

  useEffect(() => {
    const timer = window.setTimeout(() => {
      void fetchProducts();
    }, 0);
    return () => window.clearTimeout(timer);
  }, [fetchProducts]);

  const deleteProduct = useCallback(
    async (id: number) => {
      const confirmDelete = confirm("Are you sure you want to delete this product?");
      if (!confirmDelete) return;

      // Optimistic UI: remove immediately and then call API
      const previous = products;
      setProducts((p) => p.filter((prod) => prod.id !== id));
      setDeletingId(id);

      try {
        const { error } = await supabase.from("products").delete().eq("id", id);

        if (error) {
          // revert on error
          setProducts(previous);
          console.error("deleteProduct error:", error);
          alert(error.message);
          return;
        }

        // success feedback
        // Replace alert with toast in production
        alert("✅ Product Deleted");
      } catch (err) {
        setProducts(previous);
        console.error("Unexpected delete error:", err);
        alert("Failed to delete product");
      } finally {
        if (mounted.current) setDeletingId(null);
      }
    },
    [products]
  );

  const onOpenEdit = (product: Product) => {
    setSelectedProduct(product);
    setShowEditModal(true);
  };

  const onCloseEdit = () => {
    setShowEditModal(false);
    // clear selected after closing so modal doesn't hold stale product
    setTimeout(() => setSelectedProduct(null), 150); // small delay if modal animates
  };

  const currencyFormatter = new Intl.NumberFormat("en-IN", {
    style: "currency",
    currency: "INR",
    maximumFractionDigits: 2,
  });

  return (
    <main className="min-h-screen bg-slate-900 p-8 text-white">
      <div className="mx-auto max-w-7xl">
        <div className="mb-8 flex items-center justify-between">
          <h1 className="text-4xl font-bold">📦 Product Master</h1>
          <button
            onClick={() => setShowAddModal(true)}
            className="rounded-xl bg-green-600 px-5 py-3 font-semibold text-white transition hover:bg-green-500"
            aria-label="Add product"
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
                  <td colSpan={5} className="p-8 text-center">
                    Loading...
                  </td>
                </tr>
              ) : products.length === 0 ? (
                <tr>
                  <td colSpan={5} className="p-8 text-center text-neutral-300">
                    No products yet. Click &quot;Add Product&quot; to create one.
                  </td>
                </tr>
              ) : (
                products.map((product) => (
                  <tr key={product.id} className="border-t border-white/10 hover:bg-white/5">
                    <td className="p-4">{product.product_name}</td>
                    <td className="p-4">{product.brand}</td>
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

                    <td className="p-4 text-right">{currencyFormatter.format(product.selling_price)}</td>

                    <td className="p-4 text-center">
                      <button
                        onClick={() => onOpenEdit(product)}
                        className="mr-2 inline-flex items-center justify-center rounded-lg bg-yellow-500 px-3 py-2 text-white hover:bg-yellow-600"
                        aria-label={`Edit ${product.product_name}`}
                        disabled={!!deletingId}
                      >
                        ✏️
                      </button>

                      <button
                        onClick={() => deleteProduct(product.id)}
                        className="inline-flex items-center justify-center rounded-lg bg-red-500 px-3 py-2 text-white hover:bg-red-600 disabled:opacity-50"
                        aria-label={`Delete ${product.product_name}`}
                        disabled={deletingId === product.id}
                      >
                        {deletingId === product.id ? "Deleting…" : "🗑"}
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      <AddProductModal open={showAddModal} onClose={() => setShowAddModal(false)} onSaved={fetchProducts} />

      { /* Render EditProductModal only when needed to avoid unnecessary renders */ }
      <EditProductModal key={selectedProduct?.id} open={showEditModal} product={selectedProduct} onClose={onCloseEdit} onSaved={() => { fetchProducts(); onCloseEdit(); }} />
    </main>
  );
}
