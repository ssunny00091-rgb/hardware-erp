"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";

import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";

import {
  supplierSchema,
  SupplierFormData,
} from "@/app/lib/validations/supplier";

interface SupplierFormProps {
  onSubmit: (data: SupplierFormData) => Promise<void>;
}

export default function SupplierForm({
  onSubmit,
}: SupplierFormProps) {
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<SupplierFormData>({
    resolver: zodResolver(supplierSchema),
    defaultValues: {
      supplier_name: "",
      mobile: "",
      address: "",
      gst_no: "",
      opening_balance: 0,
    },
  });

  return (
    <form
      onSubmit={handleSubmit(async (data) => {
        await onSubmit(data);
        reset();
      })}
      className="space-y-5"
    >
      <div>
        <label className="mb-2 block text-sm font-medium">
          Supplier Name *
        </label>

        <Input
          {...register("supplier_name")}
          placeholder="Supplier Name"
        />

        {errors.supplier_name && (
          <p className="mt-1 text-sm text-red-500">
            {errors.supplier_name.message}
          </p>
        )}
      </div>

      <div>
        <label className="mb-2 block text-sm font-medium">
          Mobile
        </label>

        <Input
          {...register("mobile")}
          placeholder="9876543210"
        />
      </div>

      <div>
        <label className="mb-2 block text-sm font-medium">
          GST Number
        </label>

        <Input
          {...register("gst_no")}
          placeholder="GST Number"
        />
      </div>

      <div>
        <label className="mb-2 block text-sm font-medium">
          Address
        </label>

        <Input
          {...register("address")}
          placeholder="Supplier Address"
        />
      </div>

      <div>
        <label className="mb-2 block text-sm font-medium">
          Opening Balance
        </label>

        <Input
  type="number"
  {...register("opening_balance", {
    valueAsNumber: true,
  })}
  placeholder="0"
/>
      </div>

      <Button
        type="submit"
        disabled={isSubmitting}
        className="w-full"
      >
        {isSubmitting ? "Saving..." : "Save Supplier"}
      </Button>
    </form>
  );
}