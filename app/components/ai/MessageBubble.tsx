import { cn } from "@/lib/utils";

interface MessageBubbleProps {
  role: "user" | "assistant";
  content: string;
}

export default function MessageBubble({
  role,
  content,
}: MessageBubbleProps) {
  const isUser = role === "user";

  return (
    <div
      className={cn(
        "mb-4 flex",
        isUser ? "justify-end" : "justify-start"
      )}
    >
      <div
        className={cn(
          "max-w-[80%] rounded-2xl px-4 py-3 shadow",
          isUser
            ? "bg-blue-600 text-white"
            : "bg-slate-800 text-white"
        )}
      >
        {content}
      </div>
    </div>
  );
}