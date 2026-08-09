"use client";
import AppDrawer from "../components/shared/AppDrawer";
import { useState } from "react";
import PageHeader from "../components/shared/PageHeader";
import SearchBar from "../components/shared/SearchBar";
import EmptyState from "../components/shared/EmptyState";
import CustomerForm from "../components/customers/CustomerForm";
import { CustomerService } from "../services/customer.service";
import { CustomerFormData } from "../lib/validations/customer";
export default function CustomersPage() {
  const [search, setSearch] = useState("");
  const [drawerOpen, setDrawerOpen] = useState(false);
  async function handleCreateCustomer(data: CustomerFormData) {
  try {
    console.log("Sending Data:", data);

    await CustomerService.create(data);

    alert("Customer Saved Successfully ✅");

    setDrawerOpen(false);
  } catch (error: any) {
    console.log("FULL ERROR:", error);
    console.log("MESSAGE:", error?.message);
    console.log("DETAILS:", error?.details);
    console.log("HINT:", error?.hint);

    alert("Failed to save customer");
  }
}
   


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

        <EmptyState
  title="No Customers Found"
  description="Click '+ Add Customer' to create your first customer."
/>
<AppDrawer
  open={drawerOpen}
  onOpenChange={setDrawerOpen}
  title="Add Customer"
>

  <CustomerForm onSubmit={handleCreateCustomer} />

</AppDrawer>

      </div>
    </main>
  );
}