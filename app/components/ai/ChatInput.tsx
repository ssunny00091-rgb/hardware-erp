"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

interface ChatInputProps {
  onSend: (message: string) => void;
}

export default function ChatInput({
  onSend,
}: ChatInputProps) {
  const [text, setText] = useState("");

  function handleSend() {
    if (!text.trim()) return;

    onSend(text);

    setText("");
  }

  return (
    <div className="flex gap-2 border-t p-4">
      <Input
        value={text}
        placeholder="Ask ERP AI..."
        onChange={(e) => setText(e.target.value)}
        onKeyDown={(e) => {
          if (e.key === "Enter") {
            e.preventDefault();
            handleSend();
          }
        }}
      />

      <Button onClick={handleSend}>
        Send
      </Button>
    </div>
  );
}