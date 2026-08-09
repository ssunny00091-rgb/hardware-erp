"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";

import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";

import {
  customerSchema,
  CustomerFormData,
} from "@/app/lib/validations/customer";

interface CustomerFormProps {
  onSubmit: (data: CustomerFormData) => Promise<void>;
}

export default function CustomerForm({
  onSubmit,
}: CustomerFormProps) {
  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<CustomerFormData>({
    

    defaultValues: {
      customer_name: "",
      mobile: "",
      email: "",
      gst_no: "",
      address: "",
      city: "",
      state: "",
      pincode: "",
      credit_limit: 0,
      payment_term: 0,
      opening_balance: 0,
    },
  });

  return (
   <form
  onSubmit={handleSubmit(async (data) => {
    await onSubmit(data);
    reset();
  })}
>
      {/* Customer Name */}

      <div>
        <label className="mb-2 block text-sm font-medium">
          Customer Name *
        </label>

        <Input
          {...register("customer_name")}
          placeholder="Customer Name"
        />

        {errors.customer_name && (
          <p className="mt-1 text-sm text-red-500">
            {errors.customer_name.message}
          </p>
        )}
      </div>

      {/* Mobile */}

      <div>
        <label className="mb-2 block text-sm font-medium">
          Mobile
        </label>

        <Input
          {...register("mobile")}
          placeholder="9876543210"
        />
      </div>

      {/* Email */}

      <div>
        <label className="mb-2 block text-sm font-medium">
          Email
        </label>

        <Input
          {...register("email")}
          placeholder="abc@gmail.com"
        />

        {errors.email && (
          <p className="mt-1 text-sm text-red-500">
            {errors.email.message}
          </p>
        )}
      </div>

      <Button
        type="submit"
        disabled={isSubmitting}
        className="w-full"
      >
        {isSubmitting ? "Saving..." : "Continue"}
      </Button>
    </form>
  );
}