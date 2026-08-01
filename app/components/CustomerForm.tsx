type CustomerFormProps = {
  customerName: string;
  mobile: string;
  address: string;
  gst: string;
  onCustomerNameChange: (value: string) => void;
  onMobileChange: (value: string) => void;
  onAddressChange: (value: string) => void;
  onGstChange: (value: string) => void;
};

export default function CustomerForm({
  customerName,
  mobile,
  address,
  gst,
  onCustomerNameChange,
  onMobileChange,
  onAddressChange,
  onGstChange,
}: CustomerFormProps) {
  return (
    <>
      <input
        type="text"
        placeholder="Customer Name"
        value={customerName}
        onChange={(e) =>
          onCustomerNameChange(e.target.value)
        }
        className="mb-4 w-full rounded-lg border p-3"
      />

      <input
        type="text"
        placeholder="Mobile Number"
        value={mobile}
        onChange={(e) =>
          onMobileChange(e.target.value)
        }
        className="mb-4 w-full rounded-lg border p-3"
      />

      <input
        type="text"
        placeholder="Address"
        value={address}
        onChange={(e) =>
          onAddressChange(e.target.value)
        }
        className="mb-4 w-full rounded-lg border p-3"
      />

      <input
        type="text"
        placeholder="GST Number"
        value={gst}
        onChange={(e) =>
          onGstChange(e.target.value)
        }
        className="mb-4 w-full rounded-lg border p-3"
      />
    </>
  );
}