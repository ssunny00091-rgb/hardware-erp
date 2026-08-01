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
    <div
      style={{ background: theme.background }}
      className="relative overflow-hidden rounded-2xl p-5 text-white shadow-xl transition duration-300 hover:-translate-y-1 hover:scale-[1.02]"
    >
      {/* Paint splash design */}
      <div
        style={{ backgroundColor: theme.splashOne }}
        className="absolute -right-7 -top-7 h-28 w-28 rounded-full opacity-30"
      />
      <div
        style={{ backgroundColor: theme.splashTwo }}
        className="absolute -bottom-10 -left-8 h-32 w-32 rounded-full opacity-25"
      />
      <div
        style={{ backgroundColor: theme.splashOne }}
        className="absolute right-16 top-10 h-4 w-4 rounded-full opacity-50"
      />
      <div
        style={{ backgroundColor: theme.splashTwo }}
        className="absolute right-10 top-16 h-3 w-3 rounded-full opacity-50"
      />

      <div className="relative z-10 flex items-start justify-between gap-3">
        <div>
          <p className="text-sm font-medium text-white/85">{title}</p>
          <h2 className="mt-2 text-3xl font-bold">{value}</h2>
        </div>

        <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-white/20 text-2xl backdrop-blur-sm">
          🪣
        </div>
      </div>

      <p className="relative z-10 mt-5 text-xs text-white/75">
        Live shop summary
      </p>
    </div>
  );
}