import { useState } from "react";
import { Menu, X, Waves } from "lucide-react";
import { Button } from "@/app/components/ui/button";

export function Header() {
  const [isMenuOpen, setIsMenuOpen] = useState(false);

  const navigation = [
    { name: "主頁", href: "#home" },
    { name: "服務對象", href: "#target-groups" },
    { name: "為何選擇我們", href: "#why-kayarine" },
    { name: "聯絡我們", href: "#contact" }
  ];

  return (
    <header className="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-sm border-b border-gray-200">
      <div className="container mx-auto px-4">
        <div className="flex items-center justify-between h-16 md:h-20">
          {/* Logo */}
          <div className="flex items-center gap-2">
            <div className="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center">
              <Waves className="h-6 w-6 text-white" />
            </div>
            <span className="text-xl md:text-2xl">Kayarine.club</span>
          </div>

          {/* Desktop Navigation */}
          <nav className="hidden md:flex items-center gap-8">
            {navigation.map((item) => (
              <a
                key={item.name}
                href={item.href}
                className="text-gray-700 hover:text-orange-500 transition-colors"
              >
                {item.name}
              </a>
            ))}
          </nav>

          {/* CTA Button (Desktop) */}
          <div className="hidden md:block">
            <Button
              className="bg-orange-500 hover:bg-orange-600 text-white"
              onClick={() => window.open('https://wa.me/85212345678', '_blank')}
            >
              立即查詢
            </Button>
          </div>

          {/* Mobile Menu Button */}
          <button
            className="md:hidden p-2"
            onClick={() => setIsMenuOpen(!isMenuOpen)}
          >
            {isMenuOpen ? (
              <X className="h-6 w-6" />
            ) : (
              <Menu className="h-6 w-6" />
            )}
          </button>
        </div>

        {/* Mobile Navigation */}
        {isMenuOpen && (
          <nav className="md:hidden py-4 border-t border-gray-200">
            {navigation.map((item) => (
              <a
                key={item.name}
                href={item.href}
                className="block py-3 text-gray-700 hover:text-orange-500 transition-colors"
                onClick={() => setIsMenuOpen(false)}
              >
                {item.name}
              </a>
            ))}
            <Button
              className="w-full mt-4 bg-orange-500 hover:bg-orange-600 text-white"
              onClick={() => {
                window.open('https://wa.me/85212345678', '_blank');
                setIsMenuOpen(false);
              }}
            >
              立即查詢
            </Button>
          </nav>
        )}
      </div>
    </header>
  );
}
