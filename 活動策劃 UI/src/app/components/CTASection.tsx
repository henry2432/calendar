import { Phone, Mail, MessageCircle } from "lucide-react";
import { Button } from "@/app/components/ui/button";

export function CTASection() {
  return (
    <section className="py-16 md:py-24 bg-gray-50">
      <div className="container mx-auto px-4">
        <div className="max-w-4xl mx-auto text-center">
          <h2 className="text-3xl md:text-4xl lg:text-5xl mb-6">
            準備好為您的團隊打造難忘體驗？
          </h2>
          <p className="text-lg md:text-xl text-gray-600 mb-8">
            立即聯絡我們，獲取專屬定制方案和團體報價
          </p>

          <div className="flex flex-col sm:flex-row gap-4 justify-center mb-12">
            <Button
              size="lg"
              className="bg-orange-500 hover:bg-orange-600 text-white px-8 py-6 text-lg"
              onClick={() => window.open('https://wa.me/85259893466', '_blank')}
            >
              <MessageCircle className="mr-2 h-5 w-5" />
              WhatsApp 查詢
            </Button>
            <Button
              size="lg"
              variant="outline"
              className="border-orange-500 text-orange-500 hover:bg-orange-50 px-8 py-6 text-lg"
              onClick={() => window.location.href = 'tel:+85259893466'}
            >
              <Phone className="mr-2 h-5 w-5" />
              致電查詢
            </Button>
            <Button
              size="lg"
              variant="outline"
              className="border-orange-500 text-orange-500 hover:bg-orange-50 px-8 py-6 text-lg"
              onClick={() => window.location.href = 'mailto:contact@kayarine.club'}
            >
              <Mail className="mr-2 h-5 w-5" />
              電郵查詢
            </Button>
          </div>
        </div>
      </div>
    </section>
  );
}