import { ArrowRight, ChevronDown } from "lucide-react";
import { Button } from "@/app/components/ui/button";
import heroImage from "figma:asset/2be8547a3e355ab1fe896eda77c07048e32aec9e.png";

export function HeroSection() {
  return (
    <section className="relative h-screen min-h-[600px] flex items-center justify-center overflow-hidden">
      {/* Background Image with Overlay */}
      <div className="absolute inset-0 z-0">
        <img
          src={heroImage}
          alt="Team kayaking at sunset"
          className="w-full h-full object-cover"
        />
        <div className="absolute inset-0 bg-gradient-to-b from-black/60 via-black/10 to-black/70"></div>
      </div>

      {/* Content */}
      <div className="relative z-10 container mx-auto px-4 text-white">
        <div className="max-w-4xl pt-12 md:pt-16">
          <h1 className="text-3xl md:text-5xl lg:text-6xl mb-6 animate-fade-in">
            獨木舟・直立板定制活動專家
          </h1>
          <p className="text-lg md:text-xl lg:text-2xl mb-4 max-w-2xl opacity-95">
            你的首選出海活動定制
          </p>
          <p className="text-base md:text-lg mb-8 max-w-xl opacity-90">
            安全專業．客製化樂趣．增進團隊合作．自然體驗．品牌打造
          </p>

          {/* CTA Buttons */}
          <div className="flex flex-col sm:flex-row gap-4 items-start mb-8">
            <Button
              size="lg"
              className="bg-orange-500 hover:bg-orange-600 text-white px-8 py-6 text-lg"
              onClick={() => window.open('https://wa.me/85212345678', '_blank')}
            >
              立即查詢團體報價
              <ArrowRight className="ml-2 h-5 w-5" />
            </Button>
          </div>
        </div>
      </div>

      {/* Scroll Indicator */}
      <div className="absolute bottom-8 left-1/2 -translate-x-1/2 z-10 animate-bounce">
        <ChevronDown className="h-8 w-8 text-white opacity-75" />
      </div>
    </section>
  );
}