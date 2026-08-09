"use client";

import { useState } from "react";

import AppDrawer from "../components/shared/AppDrawer";
import PageHeader from "../components/shared/PageHeader";
import SearchBar from "../components/shared/SearchBar";

import CustomerForm from "../components/customers/CustomerForm";
import CustomerList from "../components/customers/CustomerList";

import { CustomerService } from "../services/customer.service";
import { CustomerFormData } from "../lib/validations/customer";
import { useCustomers } from "../hooks/useCustomers";

export default function CustomersPage() {
  const [search, setSearch] = useState("");
  const [drawerOpen, setDrawerOpen] = useState(false);

  const {
    customers,
    loading,
    refresh,
  } = useCustomers();

  async function handleCreateCustomer(
    data: CustomerFormData
  ) {
    try {
      await CustomerService.create(data);

      await refresh();

      setDrawerOpen(false);

      alert("Customer Saved Successfully ✅");
    } catch (error: any) {
      console.error(error);

      alert(error.message);
    }
  }

  const filteredCustomers = customers.filter((customer) =>
    customer.customer_name
      .toLowerCase()
      .includes(search.toLowerCase())
  );

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

        <CustomerList
  customers={filteredCustomers}
  loading={loading}
/>

        <AppDrawer
          open={drawerOpen}
          onOpenChange={setDrawerOpen}
          title="Add Customer"
        >
          <CustomerForm
            onSubmit={handleCreateCustomer}
          />
        </AppDrawer>

      </div>
    </main>
  );
}