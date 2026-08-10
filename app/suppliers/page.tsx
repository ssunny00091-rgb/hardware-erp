"use client";

import { useState } from "react";

import AppDrawer from "../components/shared/AppDrawer";
import PageHeader from "../components/shared/PageHeader";
import SearchBar from "../components/shared/SearchBar";

import SupplierForm from "../components/suppliers/SupplierForm";
import SupplierList from "../components/suppliers/SupplierList";

import { useSuppliers } from "../hooks/useSuppliers";
import { SupplierService } from "../services/supplier.service";
import { SupplierFormData } from "../lib/validations/supplier";

export default function SuppliersPage() {
  const [search, setSearch] = useState("");
  const [drawerOpen, setDrawerOpen] = useState(false);

  const {
    suppliers,
    loading,
    refresh,
  } = useSuppliers();

  async function handleCreateSupplier(
    data: SupplierFormData
  ) {
    try {
      await SupplierService.create(data);

      await refresh();

      setDrawerOpen(false);

      alert("Supplier Saved Successfully ✅");
    } catch (error: any) {
      console.error("Create Supplier:", error);

      alert(
        error?.message || "Failed to save supplier"
      );
    }
  }

  const filteredSuppliers = suppliers.filter(
    (supplier) =>
      supplier.supplier_name
        .toLowerCase()
        .includes(search.toLowerCase()) ||
      supplier.mobile
        ?.toLowerCase()
        .includes(search.toLowerCase())
  );

  return (
    <main className="min-h-screen bg-slate-900 p-8 text-white">
      <div className="mx-auto max-w-7xl">

        <PageHeader
          title="Suppliers"
          description="Manage your suppliers"
          actionLabel="+ Add Supplier"
          onAction={() => setDrawerOpen(true)}
        />

        <SearchBar
          value={search}
          onChange={setSearch}
          placeholder="Search Supplier..."
        />

        <SupplierList
          suppliers={filteredSuppliers}
          loading={loading}
        />

        <AppDrawer
          open={drawerOpen}
          onOpenChange={setDrawerOpen}
          title="Add Supplier"
        >
          <SupplierForm
            onSubmit={handleCreateSupplier}
          />
        </AppDrawer>

      </div>
    </main>
  );
}