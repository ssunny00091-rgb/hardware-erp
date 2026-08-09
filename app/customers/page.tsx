"use client";
import AppDrawer from "../components/shared/AppDrawer";
import { useState } from "react";
import PageHeader from "../components/shared/PageHeader";
import SearchBar from "../components/shared/SearchBar";
import EmptyState from "../components/shared/EmptyState";

export default function CustomersPage() {
  const [search, setSearch] = useState("");
  const [drawerOpen, setDrawerOpen] = useState(false);


  return (
    <main className="min-h-screen bg-slate-900 p-8">
      <div className="mx-auto max-w-7xl">

 <PageHeader
  title="Customers"
  description="Manage your customers"
  actionLabel="+ Add Customer"
  onAction={() => setDrawerOpen(true)}
/>
<SearchBar
  value={search}
  onChange={setSearch}
  placeholder="Search Customer..."
/>


        <input
          type="text"
          placeholder="🔍 Search Customer..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="mb-8 w-full rounded-xl border border-white/20 bg-white/10 p-4 text-white placeholder-gray-400"
        />

        <EmptyState
  title="No Customers Found"
  description="Click '+ Add Customer' to create your first customer."
/>
<AppDrawer
  open={drawerOpen}
  onOpenChange={setDrawerOpen}
  title="Add Customer"
>

  <p className="text-muted-foreground">
    Customer Form Coming Soon...
  </p>

</AppDrawer>

      </div>
    </main>
  );
}