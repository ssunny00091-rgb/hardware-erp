export interface AIMessage {
  id: string;

  role: "user" | "assistant";

  content: string;

  createdAt: string;
}
