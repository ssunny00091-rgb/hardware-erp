import { motion } from "framer-motion";
type DashboardCardProps = {
  title: string;
  value: string;
};

type CardTheme = {
  background: string;
  splashOne: string;
  splashTwo: string;
};

const themes: CardTheme[] = [
  {
    background: "linear-gradient(135deg, #2563eb 0%, #7c3aed 100%)",
    splashOne: "#60a5fa",
    splashTwo: "#c4b5fd",
  },
  {
    background: "linear-gradient(135deg, #f97316 0%, #ef4444 100%)",
    splashOne: "#fdba74",
    splashTwo: "#fca5a5",
  },
  {
    background: "linear-gradient(135deg, #059669 0%, #14b8a6 100%)",
    splashOne: "#6ee7b7",
    splashTwo: "#5eead4",
  },
  {
    background: "linear-gradient(135deg, #9333ea 0%, #ec4899 100%)",
    splashOne: "#d8b4fe",
    splashTwo: "#f9a8d4",
  },
];

function getThemeIndex(title: string) {
  return [...title].reduce((total, character) => {
    return total + character.charCodeAt(0);
  }, 0) % themes.length;
}



export default function DashboardCard({
  title,
  value,
}: DashboardCardProps) {
  const theme = themes[getThemeIndex(title)];

 return (
  <motion.div
    initial={{ opacity: 0, y: 25 }}
animate={{ opacity: 1, y: 0 }}
transition={{
  duration: 0.22,
  ease: "easeOut",
}}
whileHover={{
  scale: 1.03,
  y: -8,
  transition: {
    duration: 0.15,
  },
}}
whileTap={{
  scale: 0.98,
}}
    className="relative overflow-hidden rounded-2xl p-6 text-white shadow-xl"
  >
    {/* Background Circle */}
    <div
      className="absolute -right-10 -top-10 h-32 w-32 rounded-full opacity-20"
      style={{ background: theme.splashOne }}
    />

    <div
      className="absolute -bottom-12 -left-12 h-40 w-40 rounded-full opacity-20"
      style={{ background: theme.splashTwo }}
    />

    <div className="relative z-10">
      <h3 className="text-lg font-semibold">
        {title}
      </h3>

      <p className="mt-3 text-4xl font-bold">
        {value}
      </p>
    </div>
  </motion.div>
);
}