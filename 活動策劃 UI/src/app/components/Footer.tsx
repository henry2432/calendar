import { Waves, Facebook, Instagram, Mail, Phone } from "lucide-react";

export function Footer() {
  return (
    <footer className="bg-gray-900 text-white py-12">
      <div className="container mx-auto px-4">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
          {/* Brand */}
          <div className="md:col-span-1">
            <div className="flex items-center gap-2 mb-4">
              <div className="w-10 h-10 bg-orange-500 rounded-full flex items-center justify-center">
                <Waves className="h-6 w-6 text-white" />
              </div>
              <span className="text-xl">Kayarine.club</span>
            </div>
            <p className="text-gray-400 text-sm">
              香港西貢專業團體水上活動提供者
            </p>
          </div>

          {/* Quick Links */}
          <div>
            <h3 className="mb-4">快速連結</h3>
            <ul className="space-y-2 text-gray-400 text-sm">
              <li><a href="#home" className="hover:text-orange-500 transition-colors">主頁</a></li>
              <li><a href="#target-groups" className="hover:text-orange-500 transition-colors">服務對象</a></li>
              <li><a href="#why-kayarine" className="hover:text-orange-500 transition-colors">為何選擇我們</a></li>
              <li><a href="#contact" className="hover:text-orange-500 transition-colors">聯絡我們</a></li>
            </ul>
          </div>

          {/* Services */}
          <div>
            <h3 className="mb-4">我們的服務</h3>
            <ul className="space-y-2 text-gray-400 text-sm">
              <li>獨木舟課程</li>
              <li>直立板課程</li>
              <li>團體旅程</li>
              <li>Team Building 活動</li>
              <li>證書課程</li>
            </ul>
          </div>

          {/* Contact */}
          <div>
            <h3 className="mb-4">聯絡我們</h3>
            <ul className="space-y-3 text-gray-400 text-sm">
              <li className="flex items-center gap-2">
                <Phone className="h-4 w-4" />
                <a href="tel:+85259893466" className="hover:text-orange-500 transition-colors">
                  +852 5989 3466
                </a>
              </li>
              <li className="flex items-center gap-2">
                <Mail className="h-4 w-4" />
                <a href="mailto:contact@kayarine.club" className="hover:text-orange-500 transition-colors">
                  contact@kayarine.club
                </a>
              </li>
              <li className="flex items-center gap-3 mt-4">
                <a href="#" className="hover:text-orange-500 transition-colors">
                  <Facebook className="h-5 w-5" />
                </a>
                <a href="#" className="hover:text-orange-500 transition-colors">
                  <Instagram className="h-5 w-5" />
                </a>
              </li>
            </ul>
          </div>
        </div>

        {/* Bottom Bar */}
        <div className="border-t border-gray-800 pt-8 text-center text-gray-400 text-sm">
          <p>&copy; 2026 Kayarine.club. All rights reserved.</p>
        </div>
      </div>
    </footer>
  );
}